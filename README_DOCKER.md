Quick Docker run and deploy notes

Run locally:

```bash
# build image
docker compose build --no-cache
# run (app available at http://localhost:8080)
docker compose up -d
# view logs
docker compose logs -f
```

Environment:
- Set production environment variables before running, e.g. `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_SSL_CA` (paste PEM content or path).

Deploy options:
- Render: Create a new Web Service, choose "Docker" and point to this repo. Provide environment variables in Render dashboard.
- DigitalOcean App Platform: Register a service using Dockerfile; set env vars.
- VPS: Build and run the Docker image on any host and configure a reverse proxy / TLS.

Notes:
- This bypasses Vercel serverless limitations (PHP+OpenSSL outbound TLS to custom ports).
- After deploying the containerized app, verify `?debug_db=env2026` and remove debug endpoints when resolved.
