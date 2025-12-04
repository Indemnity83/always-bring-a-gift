# Authentik SSO Integration

This application uses Authentik for Single Sign-On (SSO) authentication via OAuth2/OpenID Connect.

## How It Works

### OAuth Flow
1. User clicks "Sign in with Authentik" on the login page
2. Application redirects to Authentik with OAuth credentials (`/auth/authentik`)
3. User authenticates on Authentik (your SSO server)
4. Authentik redirects back to application with authorization code (`/auth/authentik/callback`)
5. Application exchanges code for user information
6. Application creates or updates local user record
7. User is logged in and redirected to dashboard

### Local User Management
- Users are **created automatically** on first SSO login
- User data is synced from Authentik (name, email)
- Email is automatically verified (`email_verified_at` set to now)
- Local user record is updated on each login

## Production Setup

### 1. Configure Authentik

In your Authentik admin panel:

1. **Create OAuth2/OpenID Provider:**
   - Go to Applications → Providers → Create
   - Type: OAuth2/OpenID Connect
   - Name: "Gifts App" (or your app name)
   - Authorization flow: `authorization-code`
   - Client type: `confidential`
   - Redirect URIs: `https://yourdomain.com/auth/authentik/callback`
   - **Property Mappings** (IMPORTANT):
     - Under "Scopes", ensure these are selected:
       - `authentik default OAuth Mapping: OpenID 'openid'`
       - `authentik default OAuth Mapping: OpenID 'profile'`
       - `authentik default OAuth Mapping: OpenID 'email'`
     - These mappings ensure email, name, and other user attributes are included in the response

2. **Create Application:**
   - Go to Applications → Applications → Create
   - Name: "Gifts App"
   - Slug: `gifts-app`
   - Provider: Select the provider you just created
   - Launch URL: `https://yourdomain.com`

3. **Get Credentials:**
   - Click on your provider
   - Copy **Client ID** and **Client Secret**

### 2. Configure Application

Add to your `.env` file:

```env
# Authentik SSO Configuration
AUTHENTIK_CLIENT_ID=your_client_id_here
AUTHENTIK_CLIENT_SECRET=your_client_secret_here
AUTHENTIK_REDIRECT_URI=https://yourdomain.com/auth/authentik/callback
AUTHENTIK_BASE_URL=https://authentik.yourdomain.com
```

### 3. Test the Integration

1. Visit `/login`
2. Click "Sign in with Authentik"
3. Authenticate on Authentik
4. You should be redirected back and logged in

## Development Setup

Since you can't access Authentik from your local development environment, you have **two options**:

### Option 1: Use Regular Email/Password (Recommended for Development)

The application uses Laravel Fortify, which provides traditional email/password authentication. **This works alongside Authentik SSO.**

**How it works:**
- Authentik button only shows if `AUTHENTIK_CLIENT_ID` is configured
- In development, leave Authentik credentials blank in `.env`
- Login page won't show Authentik button
- Use regular email/password authentication

**Development .env:**
```env
# Leave Authentik blank for development
AUTHENTIK_CLIENT_ID=
AUTHENTIK_CLIENT_SECRET=
AUTHENTIK_REDIRECT_URI=
AUTHENTIK_BASE_URL=
```

**Register a dev user:**
```bash
php artisan tinker
>>> User::create(['name' => 'Dev User', 'email' => 'dev@test.com', 'password' => Hash::make('password'), 'email_verified_at' => now()])
```

### Option 2: Run Authentik Locally (Docker)

Run Authentik in Docker for local testing:

```bash
# docker-compose.yml
version: '3.8'
services:
  authentik:
    image: ghcr.io/goauthentik/server:latest
    ports:
      - "9000:9000"
    environment:
      - AUTHENTIK_SECRET_KEY=your-local-secret-key
      # ... other required env vars
```

Then configure with `http://localhost:9000` as the base URL.

## Troubleshooting

### "Invalid Grant" Error (400 Bad Request)
This error typically means:

1. **Redirect URI Mismatch** - Most common cause
   - Check `.env`: `AUTHENTIK_REDIRECT_URI=https://yourdomain.com/auth/authentik/callback`
   - In Authentik provider settings, ensure "Redirect URIs" includes the **exact** URL
   - Must match **exactly** - including protocol (https), domain, and path
   - **Important**: Some reverse proxies (like Traefik/nginx) may need special configuration

2. **Wrong Client Credentials**
   - Verify `AUTHENTIK_CLIENT_ID` matches the provider's Client ID in Authentik
   - Verify `AUTHENTIK_CLIENT_SECRET` matches the provider's Client Secret
   - Check for extra spaces or line breaks in `.env`

3. **Authentik Configuration**
   - Provider must use **Authorization Code** flow (not implicit)
   - Client type should be **Confidential** (not public)
   - Required scopes: `openid`, `profile`, `email`

4. **HTTPS Requirements**
   - Production redirect URIs must use `https://`
   - Ensure your `APP_URL` in `.env` matches your actual URL
   - Check `config('app.url')` returns the correct HTTPS URL

### Connection Timeouts
- Verify `AUTHENTIK_BASE_URL` is accessible from your server
- Check firewall rules allow outbound HTTPS to Authentik server
- Test connection: `curl -I https://your-authentik-url.com`

### "Unable to authenticate with Authentik"
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify Authentik credentials in `.env`
- Confirm redirect URI matches exactly in Authentik config
- Ensure Authentik is accessible from your server

### Reverse Proxy Configuration
If behind a reverse proxy (Traefik, nginx, Caddy), ensure:

**For Traefik/nginx:**
```conf
# Ensure X-Forwarded headers are set
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-Host $host;
```

**Laravel Trusted Proxies:**
```env
# In .env - trust all proxies (or specify proxy IPs)
TRUSTED_PROXIES=*
```

### Users can't register via Authentik
- This is **expected behavior** - users are auto-created on first SSO login
- No manual registration is needed
- User data is synced from Authentik

### Want to disable traditional login in production?
- Remove the email/password form from `resources/views/livewire/auth/login.blade.php`
- Keep only the Authentik button
- Or create a custom Fortify view that only shows SSO

## Security Notes

- **Never commit** `.env` with real Authentik credentials
- Use HTTPS in production for redirect URIs
- Authentik handles password security (app doesn't store passwords for SSO users)
- Local password authentication still works (for dev or emergency access)
- Consider disabling registration route in production if using SSO exclusively

## File Structure

```
app/
├── Http/Controllers/Auth/
│   └── AuthentikController.php    # Handles OAuth flow
├── Providers/
│   └── AppServiceProvider.php     # Registers Authentik provider
config/
├── services.php                    # Authentik configuration
routes/
├── web.php                         # OAuth routes
resources/views/livewire/auth/
├── login.blade.php                 # Login view with SSO button
```

## Additional Resources

- [Authentik Documentation](https://goauthentik.io/docs/)
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [SocialiteProviders Authentik](https://socialiteproviders.com/Authentik/)
