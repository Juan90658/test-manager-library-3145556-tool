// Minimal API client. Everything goes through the HTTP contract (data-model §9: the seeder never uses SQL).

export class ApiError extends Error {
  constructor(status, body) {
    super(`${status} ${body?.code ?? ''} ${body?.message ?? ''}`.trim());
    this.status = status;
    this.body = body;
  }
}

export async function login(baseUrl, username, password) {
  const res = await fetch(`${baseUrl}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  });
  const body = await res.json().catch(() => null);
  if (!res.ok) throw new ApiError(res.status, body);
  return client(baseUrl, body.access_token);
}

export function client(baseUrl, token) {
  const call = async (method, path, body) => {
    const res = await fetch(`${baseUrl}${path}`, {
      method,
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = res.status === 204 ? null : await res.json().catch(() => null);
    if (!res.ok) throw new ApiError(res.status, data);
    return data;
  };
  return {
    get: (p) => call('GET', p),
    post: (p, b) => call('POST', p, b ?? {}),
    put: (p, b) => call('PUT', p, b),
  };
}

export async function waitForHealth(baseUrl, tries = 60) {
  for (let i = 0; i < tries; i++) {
    try {
      const res = await fetch(`${baseUrl}/health`);
      if (res.ok) return;
    } catch {
      // not up yet
    }
    await new Promise((r) => setTimeout(r, 2000));
  }
  throw new Error(`API not reachable at ${baseUrl}`);
}

/** Builds a valid-looking 13-digit ISBN (978 prefix + 9 digits + EAN-13 check digit). */
export function isbn(n) {
  const body = `978${String(n).padStart(9, '0')}`;
  const sum = [...body].reduce((acc, d, i) => acc + Number(d) * (i % 2 === 0 ? 1 : 3), 0);
  return body + ((10 - (sum % 10)) % 10);
}
