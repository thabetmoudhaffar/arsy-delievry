Deploying to Render (Docker)

1) Connect your GitHub repository to Render:
   - Go to https://render.com and create an account (or sign in).
   - Create a new "Web Service" and select your repository.
   - Render will detect `render.yaml`; accept the detected service or create manually.

2) Configure environment variables (required):
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `DB_SSL_CA` (paste PEM content as the value, sensitive)
   - `DB_SSL_MODE` (set to `require`)
   - `DB_SSL_VERIFY_SERVER_CERT` (set to `true`)
   - `APP_ENV=production`
   - `SESSION_SAVE_PATH` (optional, defaults to container temp)

3) Deploy:
   - After connecting the repo, Render will build the Docker image using `Dockerfile` and deploy.
   - Check the service logs in Render to confirm `?debug_db=env2026` returns success.

Notes:
- This containerized deployment avoids Vercel serverless TLS limitations; it runs a full PHP+OpenSSL environment allowing outbound TLS to Aiven's MySQL port.
- Keep the debug endpoints only during troubleshooting; remove them after you confirm the DB connection works.
