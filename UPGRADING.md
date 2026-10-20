# Upgrading

Run any database migrations in `data/sql/incremental/`.

## 1.2.1 — Domain cancellation

The cancellation changes require no database migration. Only the existing
`cancelled_at` column is written, after a successful DELETE acknowledgement or
an Openprovider lookup confirming that the domain object is deleted.

Every cancellation attempt first checks the registrar. If an earlier DELETE
succeeded but its response was lost, the timestamp can be recovered without
another DELETE. If the object still exists, cancellation can be retried. Failed
or inconclusive lookups leave the timestamp empty for a later orphan pass.

The API returns `true` for confirmed cancellation or an object with `status` and
`message` for errors. Documented validation, authorization, and policy refusals
are `failed`; timeouts, HTTP 500, internal API errors, and inconclusive reads
are `unknown`. These distinctions are returned and logged, not stored in new
columns. After an uncertain DELETE the endpoint checks for confirmed deletion
once more; otherwise the next orphan run may retry. No durable retry lock is kept.

Hosting deletion continues even when domain cancellation fails. Registrations
survive client deletion, and the next orphan pass finds them using their stored
owner IDs and domain relationships. DNS cleanup happens after confirmation;
its errors are logged without clearing the cancellation timestamp.

Existing timestamps are preserved. Older versions could write them after a
failed registrar request, so historical timestamps still need a registrar audit.
