# Source inspection — 1.0.0

Scope: supplied ZIP only; static inspection on 2026-10-05. No code changes. WordPress.org approval and an existing GitHub repository were not independently verified. This is not a comprehensive security audit.

Verified: plugin header/version and Stable tag are 1.0.0; GPL license present; referenced local include files exist; JavaScript passes node --check; inspected admin handlers use manage_woocommerce and nonces; public unsubscribe validates a per-user token using hash_equals; marketing email goes through wp_mail; HPOS compatibility is declared; personal data exporter/eraser is implemented.

## Follow-up findings

1. **Permission confirmation differs by entry point.** class-rejoyan-crm-admin.php handle_create_campaign and handle_send_offer require marketing_permission_confirmed, but handle_campaign_action's queue branch calls queue_campaign without that confirmation. Privileged admins still need capability and nonce, so this is a workflow inconsistency, not an unauthenticated access finding. Require confirmation in each sending entry point or persist and validate a campaign permission decision.
2. **Concurrent-worker duplicate send risk.** class-rejoyan-crm-campaigns.php send_batch selects pending recipients and sends before updating status, with no visible atomic claim/lock. schedule_action/recovery can enqueue work again. Overlapping workers could select the same recipients. Reproduce with multiple workers, then add atomic recipient claims/lease recovery if needed. A crash after wp_mail succeeds and before status update also needs explicit retry policy; exactly-once email cannot be inferred.
3. **Uninstall description mismatch.** readme suggests tables are removed, while uninstall.php uses DELETE FROM and deliberately keeps schema. Align documentation with retained tables or implement intentional table removal in a later release.
4. **Missing listing artwork.** readme describes six screenshots; supplied ZIP includes none. Add genuine screenshots to top-level SVN assets. Runtime CSS/JS belong in trunk/assets instead.
5. **Compatibility claims need recorded tests.** readme says WordPress Tested up to 7.1; header says WooCommerce tested up to 11.1. Supplied archive contains no evidence of those runs. They may be valid, but should only remain as actual tested versions.
6. **Privacy preference behavior needs review.** erase_data removes the unsubscribed user meta; all-customer sending excludes users based on that flag. If the WordPress account remains, deleting the suppression flag could make that customer eligible again. Test and decide how to preserve the suppression preference without retaining erased campaign data.

wp_mail true indicates handoff to configured mail transport, not inbox delivery; treat “sent” statistics accordingly.

## Limits

PHP binary is unavailable in this workspace, so PHP lint/Plugin Check was not run. No WordPress, WooCommerce, database, Action Scheduler, SMTP or live browser integration test was run. JS syntax checking is not functional verification. Source packaging includes a SHA256 manifest to verify original bytes.
