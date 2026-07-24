# S3 Avatars for Flarum (`gradba/flarum-s3-avatars`)

Stores user avatars in an **S3-compatible bucket** (built against **Cloudflare R2**;
works with AWS S3, MinIO, Backblaze B2) instead of local disk — so Flarum can run
as a **stateless container**.

## Why not just relocate every disk?

Existing "all disks to S3" extensions replace Flarum's built-in `local` driver, so
*everything* moves — including `forum.js` / `forum.css`. That's usually wrong:

| Disk | Contents | Belongs |
|---|---|---|
| `flarum-assets` | compiled JS/CSS bundles | **In the image** — build artifacts, versioned with the deploy |
| `flarum-avatars` | user-uploaded avatars | **Object storage** — runtime data that must survive pod restarts |

This extension registers a **named** driver and points **only** `flarum-avatars`
at it (`disk_driver.flarum-avatars`). The `local` driver is never touched, so a
misconfiguration can't take your site's styling down with it.

## Safety

- If the S3 config is incomplete, the disk **falls back to local** and logs a warning.
- Flarum resolves an unknown driver name back to `local`, so disabling the
  extension restores local avatars automatically.
- The migration command **copies** by default and verifies size before it will
  delete anything.

## Configuration

Enable the extension, then either:

**Reuse FoF Upload's bucket** (default) — if `fof/upload` already stores
attachments in the bucket you want, avatars go to the same place, no second copy
of the credentials.

**Or configure a bucket** in Admin → Extensions → S3 Avatars.

**Or use environment variables** — these override everything above, which is how
you should supply them in Kubernetes (from a `Secret`):

| Variable | Notes |
|---|---|
| `GRADBA_AVATARS_S3_KEY` | required |
| `GRADBA_AVATARS_S3_SECRET` | required |
| `GRADBA_AVATARS_S3_BUCKET` | required |
| `GRADBA_AVATARS_S3_REGION` | `auto` for R2 |
| `GRADBA_AVATARS_S3_URL` | public base URL, e.g. `https://cdn.example.com` |
| `GRADBA_AVATARS_S3_ENDPOINT` | S3 API endpoint (**not** the public URL) |
| `GRADBA_AVATARS_S3_PATH_STYLE` | **required `true` for R2** |
| `GRADBA_AVATARS_S3_CACHE_CONTROL` | seconds, e.g. `31536000` |

### Cloudflare R2 notes

- Endpoint is the **bare account endpoint** — `https://<account>.r2.cloudflarestorage.com`,
  with **no bucket appended**. The bucket is its own field.
- **Path-style must be on.** R2 does not serve virtual-hosted style on that endpoint.
- Leave ACL unset — R2 rejects ACLs. Use a **custom domain** on the bucket for the
  public URL (`r2.dev` is rate-limited and not for production).

Avatars are stored under the `assets/avatars` key prefix, mirroring the local layout.

## Migrating existing avatars

```bash
php flarum gradba:avatars:migrate --dry-run   # report only
php flarum gradba:avatars:migrate             # copy, keep local files
php flarum gradba:avatars:migrate --delete-local
```

## Requires

Flarum `^1.8`, `league/flysystem-aws-s3-v3` `^1.0`.

## License

MIT — see [LICENSE](LICENSE).
