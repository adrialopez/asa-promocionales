#!/usr/bin/env python3
"""Cliente local de exploración de la API de Promo Opción.

La API solo admite 3 inicios de sesión por email cada 24 h: el token se guarda
en .token.json y no se reintenta nunca un login fallido de forma automática.

Uso:  python3 tools/promo_api.py 'query { __typename }' [salida.json]
"""
import json, os, pathlib, sys, time, urllib.request, urllib.error

ROOT = pathlib.Path(__file__).resolve().parent.parent
URL = 'https://www.promocionalesenlinea.net/graphql'


def env():
    out = {}
    for line in (ROOT / '.env').read_text().splitlines():
        if line and not line.startswith('#') and '=' in line:
            k, _, v = line.partition('=')
            out[k.strip()] = v.strip()
    return out


def post(query, variables=None, token=None):
    headers = {'Content-Type': 'application/json', 'User-Agent': 'catalog-sync/0.1'}
    if token:
        headers['Authorization'] = 'Bearer ' + token
    body = json.dumps({'query': query, 'variables': variables or {}}).encode()
    req = urllib.request.Request(URL, data=body, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=180) as r:
            return r.status, json.load(r)
    except urllib.error.HTTPError as e:
        raw = e.read().decode('utf-8', 'replace')
        try:
            return e.code, json.loads(raw)
        except ValueError:
            return e.code, {'raw': raw[:500]}


def token():
    cache = ROOT / '.token.json'
    if cache.exists():
        data = json.loads(cache.read_text())
        if time.time() - data['at'] < 23 * 3600:
            return data['token']
    lock = ROOT / '.login-failed'
    if lock.exists() and time.time() - lock.stat().st_mtime < 24 * 3600:
        sys.exit('Hubo un login fallido en las últimas 24 h. Revisar .env y borrar .login-failed a mano.')
    e = env()
    status, res = post(
        'mutation($email:String!,$password:String!){login(email:$email,password:$password){message accessToken}}',
        {'email': e['PROMO_EMAIL'], 'password': e['PROMO_PASSWORD']})
    tk = ((res.get('data') or {}).get('login') or {}).get('accessToken')
    if not tk:
        lock.touch()
        errs = [(x.get('message'), (x.get('extensions') or {}).get('code')) for x in res.get('errors', [])]
        sys.exit(f'Login fallido (HTTP {status}): {errs or res}')
    fd = os.open(cache, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(fd, 'w') as f:
        json.dump({'token': tk, 'at': time.time()}, f)
    print('Login correcto; token guardado.', file=sys.stderr)
    return tk


if __name__ == '__main__':
    status, res = post(sys.argv[1], token=token())
    text = json.dumps(res, ensure_ascii=False, indent=1)
    if len(sys.argv) > 2:
        pathlib.Path(sys.argv[2]).write_text(text)
        print(status, len(text), 'bytes ->', sys.argv[2])
    else:
        print(status, text)
