# SECURITY-RULES.md

## 1. Status Dokumen

Dokumen ini bersifat normatif. Kata **WAJIB**, **DILARANG**, **SEHARUSNYA**, dan **BOLEH** menyatakan tingkat kewajiban implementasi. Kebutuhan bisnis tidak boleh digunakan untuk melewati kontrol keamanan tanpa risk acceptance tertulis.

## 2. Tujuan Keamanan

Sistem harus menjaga:

- kerahasiaan data antar-warehouse;
- integritas stok, approval, request, PO, penerimaan, retur, dan audit;
- ketersediaan proses operasional;
- non-repudiation untuk keputusan penting;
- least privilege;
- traceability;
- keamanan file bukti;
- keamanan integrasi Google, push provider, object storage, dan Python ML.

## 3. Threat Model Minimum

Tim wajib memodelkan dan menguji setidaknya ancaman berikut:

1. user tenant mengakses record tenant lain melalui IDOR;
2. `app_admin` menaikkan dirinya menjadi `super_admin`;
3. Staff Admin melakukan approval final;
4. Koperasi membaca request koperasi lain;
5. forged Google callback atau OAuth account linking takeover;
6. bypass MFA melalui recovery atau session lama;
7. session fixation, cookie theft, dan CSRF;
8. brute force/login abuse dan invitation enumeration;
9. malicious file upload;
10. signed URL bocor atau berlaku terlalu lama;
11. double-submit menghasilkan transaksi stok/approval ganda;
12. race condition mengubah saldo stok secara salah;
13. job queue berjalan tanpa tenant context;
14. cache key atau broadcast channel mencampur tenant;
15. mass assignment `warehouse_id`, role, permission, atau status;
16. SQL injection, XSS, SSRF, path traversal, command injection;
17. audit log diubah/dihapus;
18. secret bocor di Git, log, exception, atau client bundle;
19. supply-chain compromise pada Composer/npm/agent skill;
20. Python ML mengembalikan payload berbahaya, lambat, atau data tenant salah;
21. `super_admin` menyalahgunakan impersonation;
22. export/report membocorkan data lintas tenant;
23. backup tidak terenkripsi atau restore tidak teruji;
24. notification link menunjuk record yang tidak diizinkan;
25. barcode/foto input digunakan untuk memasukkan data tak tervalidasi.

Threat model diperbarui saat ada modul, integrasi, atau role baru.

## 4. Identity Architecture

### 4.1 Invitation-Only

- Public registration WAJIB dinonaktifkan pada production.
- User memperoleh akses hanya melalui invitation aktif yang dibuat actor berwenang.
- Invitation terikat ke email, warehouse, role awal, waktu kedaluwarsa, nonce/token sekali pakai, dan inviter.
- Token invitation disimpan dalam bentuk hash.
- Resend invitation membuat token baru dan mencabut token lama.
- Pesan error tidak boleh mengungkap apakah suatu email memiliki akun.

### 4.2 Google Sign-In

- Gunakan Laravel Socialite dengan provider `google`.
- OAuth `state` WAJIB diverifikasi.
- Redirect URI harus exact-match dan HTTPS pada non-local environment.
- Setelah callback, sistem memverifikasi provider user ID, email terverifikasi, invitation/membership aktif, status user, dan status warehouse.
- Akun tidak boleh ditautkan hanya berdasarkan email tanpa verifikasi provider dan re-authentication.
- Simpan Google subject/provider ID; email dapat berubah.
- Scope Google harus minimal: identitas dasar dan email.
- Token Google tidak disimpan kecuali benar-benar diperlukan. Bila disimpan, token dienkripsi dan lifecycle/rotation didefinisikan.
- Login sukses dari Google tidak berarti authorization sukses.
- Tenant dapat membatasi domain Google, tetapi domain check tidak menggantikan invitation.

### 4.3 Local/Break-Glass Authentication

- Login password biasa DILARANG untuk user normal apabila Google Sign-In aktif sebagai kebijakan production.
- Akun break-glass hanya boleh untuk skenario outage provider dan jumlahnya minimal.
- Credential break-glass disimpan di password vault, menggunakan password acak panjang, MFA kuat/passkey, alert real-time, dan review setiap penggunaan.
- Break-glass login harus menghasilkan security event severity tinggi.

### 4.4 MFA

- MFA WAJIB untuk seluruh akun production.
- Minimal mendukung TOTP dan recovery codes melalui Laravel Fortify.
- Passkey/WebAuthn direkomendasikan dan diprioritaskan untuk `super_admin`, `app_admin`, Kepala Gudang, dan Purchasing.
- Enrollment selesai hanya setelah challenge berhasil.
- Recovery codes ditampilkan sekali, disimpan terenkripsi/hashed sesuai kemampuan framework, dan dapat diregenerasi setelah re-authentication.
- Menonaktifkan MFA memerlukan step-up authentication dan permission khusus.
- Reset MFA oleh admin memerlukan alasan, approval/dual control untuk privileged accounts, dan audit.
- Step-up MFA wajib untuk impersonation, perubahan role/permission, reset MFA, revoke mass sessions, export sensitif, cancellation penting, dan tindakan platform berisiko.

## 5. Session Security

- Gunakan secure, HTTP-only, same-site cookies.
- `SESSION_SECURE_COOKIE=true` pada production.
- Session ID dirotasi setelah login, MFA, privilege change, dan impersonation.
- Session disimpan di Redis atau backend server-side yang dikelola aman.
- Idle timeout dan absolute timeout ditetapkan; privileged role memiliki timeout lebih ketat.
- User dapat melihat dan mencabut session/device.
- Perubahan password/MFA, suspend user, atau revoke access harus mencabut session relevan.
- Remember-me untuk privileged roles sebaiknya dinonaktifkan.
- CSRF protection wajib pada semua mutating web routes.
- API routes tidak menggunakan session tanpa Sanctum/stateful configuration yang tepat.

## 6. Role, Permission, dan ACL

### 6.1 Model

Gunakan kombinasi:

- role dan permission storage melalui `spatie/laravel-permission` atau paket kompatibel yang disetujui;
- Laravel Gates untuk kemampuan non-model/platform;
- Laravel Policies untuk setiap model bisnis;
- tenant/membership check;
- Form Request `authorize()`;
- middleware untuk authentication, active tenant, active membership, MFA, dan high-risk step-up;
- query scoping dan database constraints.

Paket role/permission tidak menggantikan Policies.

### 6.2 Role Global dan Tenant

- `super_admin` adalah role platform global.
- `app_admin`, `kepala_gudang`, `staff_admin`, `purchasing`, dan `koperasi` adalah role tenant-scoped.
- Permission assignment harus menyertakan tenant/warehouse context.
- Permission cache harus menggunakan tenant-aware key dan dihapus saat role berubah.
- Role name dari request tidak boleh langsung di-assign tanpa allowlist.
- `app_admin` tidak dapat membuat atau memberikan role platform.

### 6.3 Permission Naming

Gunakan pola stabil, misalnya:

```text
platform.warehouse.viewAny
platform.warehouse.create
platform.warehouse.suspend
platform.impersonation.start

warehouse.user.viewAny
warehouse.user.invite
warehouse.user.updateRole
warehouse.user.suspend
warehouse.audit.view

item.viewAny
item.create
item.update
item.archive
stock.view
stock.adjust
stock.scanIn
stock.scanOut
purchase_request.create
purchase_request.approve
purchase_request.cancel
purchase_order.create
purchase_order.send
receipt.create
receipt.qc
pickup_request.create
pickup_request.prepare
pickup_request.approve
return.create
return.verify
return.approve
prediction.run
```

Permission wildcard hanya boleh dipakai bila semantiknya dipahami, dibuat eksplisit, dan diuji. `*` berarti seluruh action, bukan “salah satu”.

### 6.4 Segregation of Duties

- Staff Admin tidak dapat menyetujui request yang membutuhkan Kepala Gudang.
- Purchasing tidak dapat membuat PO dari request belum approved.
- Koperasi tidak dapat memutuskan retur.
- `app_admin` tidak otomatis memperoleh permission transaksi.
- Pembuat request sebaiknya tidak menjadi approver untuk workflow normal.
- Direct ML request oleh Kepala Gudang adalah pengecualian bisnis yang dicatat `AUTO_APPROVED`, bukan bypass tanpa jejak.

## 7. Model-Level Authorization

Setiap model berikut wajib memiliki Policy atau kontrol setara:

- Warehouse;
- WarehouseMembership;
- User/Invitation;
- Role/Permission assignment;
- Item/Barang;
- StockBalance;
- StockTransaction;
- Supplier;
- PickupRequest dan detail;
- PurchaseRequest dan detail/group;
- CancellationRequest;
- PurchaseOrder;
- Receipt dan QC evidence;
- Return dan return evidence;
- Approval;
- Notification;
- AuditEvent;
- Prediction;
- File/Attachment;
- Export.

Policy wajib memeriksa:

1. actor aktif;
2. warehouse aktif;
3. membership aktif;
4. permission;
5. ownership/scope;
6. status transition;
7. segregation of duties;
8. sensitivity/step-up requirement;
9. impersonation restriction bila berlaku.

`findOrFail($id)` tanpa tenant scope dan policy DILARANG untuk model tenant.

## 8. Tenant Isolation

### 8.1 Application Layer

- Tenant context ditetapkan dari membership/session, bukan request body bebas.
- Route model binding tenant model harus scoped.
- Gunakan central tenant resolver dan middleware.
- Eloquent model tenant memakai trait/interface konsisten, tetapi global scope tidak boleh menjadi satu-satunya kontrol.
- Policy tetap wajib.
- Query service/repository harus menerima `WarehouseId`/context secara eksplisit pada operasi sensitif.
- `withoutGlobalScopes()` pada tenant model dilarang kecuali di service platform ter-review dan diaudit.
- Queue job harus serialise `warehouse_id` dan memulihkan tenant context sebelum query.
- Scheduled job memproses tenant satu per satu dengan context eksplisit.
- Cache, lock, rate limiter, broadcast channel, notification, search index, file path, dan idempotency key harus menyertakan tenant ID.

### 8.2 Database Layer

- Setiap tabel tenant memiliki `warehouse_id NOT NULL` kecuali alasan kuat.
- Foreign key dan unique index memasukkan `warehouse_id` bila uniqueness bersifat tenant.
- Relasi detail-parent harus mencegah cross-tenant association melalui composite constraint bila praktis.
- PostgreSQL Row Level Security direkomendasikan sebagai defense in depth sebelum production; desain dan test harus memastikan koneksi Laravel mengatur tenant context dengan benar.
- Akun database aplikasi menggunakan least privilege; migration user dipisahkan dari runtime user.
- Runtime user tidak memiliki hak drop schema atau mengubah role.

### 8.3 Tenant Isolation Tests

Untuk setiap endpoint/action utama, test wajib membuktikan:

- tenant A dapat mengakses record A sesuai role;
- tenant A tidak dapat melihat/mengubah/download record B;
- mengganti route ID tidak bocor;
- export/search/autocomplete tidak bocor;
- notification URL tidak bocor;
- queue job tidak bocor;
- super_admin hanya mendapat akses melalui flow platform/impersonation yang benar.

## 9. Input Validation dan Mass Assignment

- Semua request mutasi menggunakan Form Request atau validator terpusat.
- `warehouse_id`, actor ID, role platform, status terminal, approval actor, audit fields, dan computed amount tidak boleh diambil langsung dari client.
- Gunakan DTO/value object untuk identifier dan quantity penting.
- Quantity wajib integer/decimal sesuai satuan, batas wajar, dan > 0 kecuali adjustment khusus.
- Enum divalidasi dengan enum class, bukan string bebas.
- Tanggal/timezone divalidasi dan dinormalisasi.
- HTML input dihindari; bila diperlukan, sanitasi allowlist.
- Model `$guarded = []` dilarang pada model sensitif tanpa pembenaran dan test.
- Update menggunakan allowlist field eksplisit.

## 10. Status Transition Security

- Status tidak boleh diubah oleh generic CRUD endpoint.
- Gunakan command/action khusus seperti `ApprovePurchaseRequest`, `RejectReturn`, `SendPurchaseOrder`, `CompletePickup`.
- Action memverifikasi status awal yang sah.
- Action terminal idempotent atau menolak replay secara aman.
- Approval decision immutable.
- Perubahan status dan side effect berada dalam transaction boundary yang jelas.
- Optimistic concurrency/version column direkomendasikan untuk record workflow.
- Double click/retry tidak menghasilkan approval, stock movement, PO, atau replacement ganda.

## 11. Stock Integrity

- Stock movement adalah ledger append-only.
- Balance diperbarui atomik dalam transaksi yang sama atau dihitung dari ledger dengan strategi konsisten.
- Update read-modify-write tanpa lock/version/atomic SQL dilarang.
- Backorder/stock negatif adalah rule bisnis, tetapi lost update tetap tidak diperbolehkan.
- Adjustment manual membutuhkan permission khusus, alasan, referensi, dan audit.
- Ledger tidak diedit/hapus; koreksi menggunakan reversing transaction.
- Reconciliation job membandingkan balance dan ledger serta menghasilkan alert.
- Barcode scan tidak langsung commit sebelum item dan quantity dikonfirmasi.

## 12. Approval dan Non-Repudiation

Audit approval minimal menyimpan:

- warehouse;
- entity type dan ID;
- decision;
- actor asli;
- actor impersonator bila ada;
- role/permission snapshot relevan;
- timestamp UTC;
- alasan penolakan/pembatalan;
- source request/correlation ID;
- IP dan user agent yang diprivasi sesuai kebijakan;
- record version/hash sebelum keputusan;
- `AUTO_APPROVED` flag untuk direct request Kepala Gudang.

Decision tidak boleh dihapus oleh user biasa.

## 13. Audit Logging

### 13.1 Event Wajib

- login success/failure/risk;
- OAuth linking/unlinking;
- MFA enroll/reset/disable/recovery;
- invitation create/resend/revoke/accept;
- user create/suspend/restore;
- role/permission change;
- warehouse create/suspend;
- impersonation start/end/failure;
- create/update/archive master data penting;
- stock movement/adjustment;
- approval/rejection;
- purchase request duplicate override;
- cancellation;
- PO send;
- receipt/QC evidence;
- return attribution/disposal/replacement;
- file access sensitif;
- export;
- ML request/response metadata;
- security control failure.

### 13.2 Integritas

- Audit log append-only pada aplikasi.
- Tidak ada update/delete UI.
- Gunakan database privilege terpisah, hash chaining, WORM storage, atau log sink eksternal untuk event kritis sesuai tingkat risiko.
- Jam server disinkronkan.
- Audit tidak menyimpan password, token, recovery code, secret, full OAuth token, atau file content.

## 14. File Upload Security

- Allowlist MIME dan extension; verifikasi content signature/magic bytes.
- Batas ukuran, jumlah, resolusi, dan total per request.
- Nama file random; abaikan nama path dari client.
- Simpan di private object storage, bukan public web root.
- Malware scanning asynchronous/synchronous sesuai risk.
- Re-encode image ke format aman; strip EXIF kecuali diperlukan.
- Cegah SVG/HTML aktif untuk bukti foto.
- Temporary signed URL memiliki TTL pendek dan policy check sebelum diterbitkan.
- Attachment record memiliki warehouse, owner, purpose, checksum, processing status, retention.
- File orphan dibersihkan oleh job terjadwal.
- Delete mengikuti retention/legal hold.

## 15. Web Security

- HTTPS wajib; HSTS pada production setelah validasi.
- CSP diterapkan dan diperketat bertahap.
- `X-Content-Type-Options: nosniff`.
- `Referrer-Policy` konservatif.
- Frame protection melalui CSP `frame-ancestors`.
- Output Blade escaped default; `{!! !!}` dilarang kecuali sanitized.
- CSRF wajib untuk session routes.
- CORS deny by default; allowlist origin eksplisit untuk API yang benar-benar perlu.
- Open redirect dicegah dengan route name/relative path allowlist.
- Error production tidak menampilkan debug.
- Trusted proxy dikonfigurasi benar agar IP/scheme tidak dapat dipalsukan.

## 16. Rate Limiting dan Abuse Protection

Rate limit terpisah untuk:

- login;
- OAuth callback error/retry;
- invitation accept/resend;
- MFA challenge/recovery;
- password/break-glass login;
- upload;
- barcode lookup;
- search/autocomplete;
- approval/cancellation action;
- export;
- ML prediction;
- notification mark-all/read;
- API/webhook.

Rate key mempertimbangkan user, tenant, IP, dan endpoint. Privileged action burst kecil. Alert dibuat untuk pola mencurigakan.

## 17. API dan Python ML Security

### 17.1 Network dan Authentication

- Endpoint ML hanya melalui HTTPS.
- Service tidak diekspos publik bila dapat ditempatkan pada private network.
- Laravel mengautentikasi ke Python menggunakan service credential yang dapat dirotasi; HMAC request signing atau mTLS direkomendasikan.
- Signature mencakup method, path, body hash, timestamp, nonce, key ID, dan tenant ID.
- Python menolak timestamp lama dan nonce replay.
- Credential per environment, tidak digunakan silang.

### 17.2 Data Minimization

- Kirim hanya item identifier pseudonymous, horizon, dan agregat histori yang diperlukan.
- Jangan kirim email, nama user, foto, supplier contact, atau data tenant lain.
- Model/training tidak boleh mencampur tenant tanpa persetujuan dan desain privacy formal.

### 17.3 Response Validation

Laravel wajib memvalidasi:

- schema response;
- request ID/idempotency;
- item/warehouse match;
- model version;
- recommendation numeric dan batas wajar;
- horizon;
- timestamp;
- status/fallback.

Response ML adalah rekomendasi, bukan command. Ia tidak dapat membuat stock movement atau PO langsung.

### 17.4 Resilience

- Timeout ketat;
- retry hanya untuk failure aman dan idempotent;
- exponential backoff/jitter;
- circuit breaker;
- no infinite retry;
- fallback sesuai PRD: `0` untuk item tanpa histori;
- failure service menghasilkan pesan aman dan tidak merusak workflow inti;
- log metadata, bukan raw sensitive payload.

## 18. Notification dan Broadcast Security

- Broadcast channel private/presence dan authorization callback tenant-aware.
- Channel name tidak boleh mudah menebak tanpa authorization.
- Payload minim; jangan kirim foto/PII/secret.
- Push notification lock-screen tidak menampilkan detail sensitif berlebihan.
- Device token terikat user/device, dienkripsi bila disimpan, dan dicabut saat logout/revoke.
- Notification link selalu policy-check saat dibuka.
- Provider failure tidak mengubah status bisnis.

## 19. Secrets Management

- Secret tidak boleh commit ke Git.
- `.env.example` hanya placeholder.
- Production menggunakan secret manager.
- Secret berbeda per environment.
- Rotation schedule untuk Google secret, FCM credential, S3 key, DB, Reverb, ML service key.
- Log sanitizer menghapus Authorization, cookie, token, password, recovery code, signature, secret, dan signed URL query.
- Secret scanning di pre-commit/CI.
- Agent prompt/output tidak boleh berisi secret production.

## 20. Dependency dan Supply Chain

- Composer/npm lockfile wajib commit.
- Dependency baru membutuhkan alasan, maintainer reputation, license, maintenance status, CVE review, dan compatibility check.
- `composer audit` dan `npm audit`/scanner ekuivalen dijalankan di CI.
- Dependabot/Renovate boleh digunakan dengan review.
- Script installer dari internet tidak dijalankan dengan privilege berlebih.
- `mattpocock/skills` dan Laravel Boost adalah development tooling; perubahan file hasil installer harus direview.
- Agent tidak boleh mengubah security policy atau dependency besar tanpa approval.
- Build artifact harus reproducible dan provenance dicatat bila platform mendukung.

## 21. Logging dan Privacy

- Gunakan structured logs dengan correlation ID, warehouse ID yang aman, actor ID, action, outcome, latency.
- Jangan log request body penuh untuk auth, file, approval reason sensitif, atau ML payload.
- PII minim dan retention ditetapkan.
- Access ke log dibatasi.
- Log tenant tidak boleh dapat dilihat tenant lain.
- Health endpoint tidak membocorkan version detail, secret, queue payload, atau stack trace.

## 22. Database Security

- TLS database pada network non-local.
- Backup terenkripsi.
- Runtime credential least privilege.
- Migration credential terpisah.
- Prepared statements/Eloquent; raw SQL dengan binding.
- Query logging production tidak menyimpan secret.
- Index dan constraints mencegah data invalid.
- Soft delete tidak digunakan sebagai pengganti authorization.
- PII field yang perlu dilindungi dienkripsi/casted sesuai threat model.
- Database dump sanitised untuk non-production.

## 23. Backup, Restore, dan Retention

- Backup database dan object storage otomatis.
- Encryption at rest dan in transit.
- Restore drill berkala, bukan hanya backup success.
- RPO/RTO ditetapkan sebelum production.
- Retention berbeda untuk audit, transaksi, notifikasi, foto, dan session.
- Purge tenant memerlukan approval, grace period, export, dan audit.
- Backup lama mengikuti deletion policy yang terdokumentasi.

## 24. CI/CD Security Gates

Pipeline minimum:

1. dependency install dari lockfile;
2. formatting/lint;
3. static analysis;
4. unit/feature tests;
5. tenant isolation tests;
6. authorization matrix tests;
7. migration up/down atau forward-only smoke test sesuai kebijakan;
8. frontend build;
9. dependency vulnerability scan;
10. secret scan;
11. SAST;
12. container/image scan bila menggunakan container;
13. browser smoke tests;
14. artifact signing/provenance bila tersedia.

Deployment production membutuhkan review dan rollback plan. Migration destructive harus menggunakan expand-migrate-contract.

## 25. Security Testing Matrix

Minimum automated tests:

- unauthenticated access denied;
- inactive user/warehouse denied;
- missing MFA denied;
- each role allow/deny matrix;
- cross-tenant model binding denied;
- mass-assignment attempt ignored/denied;
- forged status transition denied;
- duplicate/replayed approval idempotent;
- Staff Admin cannot approve;
- Purchasing cannot send unapproved PO;
- Koperasi cannot access other owner data;
- app_admin cannot assign super_admin;
- signed file URL policy/expiry;
- malicious upload rejected;
- notification link authorization;
- queue tenant context;
- broadcast channel authorization;
- OAuth state invalid rejected;
- invitation expired/replayed rejected;
- MFA recovery/reuse rejected;
- impersonation requires reason/MFA/audit;
- ML response tenant/item mismatch rejected;
- ML timeout/circuit breaker behavior;
- stock concurrent mutation integrity.

## 26. Incident Response

Sebelum production harus tersedia:

- owner dan contact incident;
- severity classification;
- revoke session/credential procedure;
- disable Google/ML/push integration safely;
- suspend tenant/user;
- preserve audit/log evidence;
- rotate secrets;
- communicate impacted tenant;
- restore service/data;
- post-incident review dan corrective action.

Security event severity tinggi minimal:

- cross-tenant access attempt sukses/gagal berulang;
- super_admin login anomali;
- break-glass use;
- MFA reset privileged user;
- role escalation;
- mass export;
- audit tamper attempt;
- secret exposure;
- ML service signature failure berulang.

## 27. Production Readiness Security Checklist

Production release dilarang sebelum semua poin berikut terpenuhi:

- [ ] public registration off;
- [ ] Google OAuth redirect/state verified;
- [ ] MFA enforced;
- [ ] bootstrap super_admin secured;
- [ ] role/permission seeded and reviewed;
- [ ] Policies present for all sensitive models;
- [ ] tenant isolation tests pass;
- [ ] queue/cache/broadcast/file tenant awareness tested;
- [ ] audit events verified;
- [ ] uploads private, validated, scanned/processed;
- [ ] HTTPS/cookie/CSRF/CSP baseline configured;
- [ ] debug off;
- [ ] secrets in secret manager;
- [ ] dependency and secret scans pass;
- [ ] backup and restore drill pass;
- [ ] alerting and incident contact configured;
- [ ] rate limits configured;
- [ ] privileged impersonation controlled;
- [ ] stock concurrency tests pass;
- [ ] destructive migrations reviewed;
- [ ] ML disabled until final phase and separately reviewed.

## 28. Prohibited Patterns

DILARANG:

```php
// Unscoped tenant lookup
Item::findOrFail($id);

// Trusting tenant from input
$warehouseId = $request->input('warehouse_id');

// Generic status mutation
$model->update(['status' => $request->status]);

// Authorization only in Blade
@if($user->isAdmin()) ... @endif

// Mass assignment of everything
Model::create($request->all());

// Public evidence storage
Storage::disk('public')->put($path, $file);

// Silent super-admin bypass without audit
Gate::before(fn ($user) => $user->role === 'super_admin' ? true : null);
```

Super-admin behavior harus dibatasi pada capability platform atau support session yang eksplisit; jangan memberi bypass universal tanpa tenant context, step-up, alasan, dan audit.
