# Tickex Application TODO

## 1. Authentication and login flow

1. Replace `YOUR_GOOGLE_CLIENT_ID` in `config.php` with your real Google OAuth client ID.
2. Register your app in Google Cloud Console:
   - create a Web OAuth 2.0 client ID
   - add `http://localhost` (or your local domain) as an authorized origin
   - add `http://localhost/tickex/google_login.php` or the equivalent URL as an allowed redirect URI if you switch to OAuth code flow
3. Confirm the `users` table schema supports:
   - `id` primary key
   - `full_name` varchar
   - `email` varchar unique
   - `password` varchar nullable
   - `auth_provider` or `google_id` if you want to support provider-specific accounts cleanly
4. Test email/password login using `login.php` and register using `register.php`.
5. Test Google sign-in on `login.php` and verify users are created or logged in correctly.
6. Add a password-reset path for users who sign in with Google but later want password login.
7. Use prepared statements or parameterized queries instead of raw string SQL.
8. Add Google sign-in to `register.php` if you want registration and login to share the same flow.

## 2. User account and profile

- Add a profile page with user name, email, booking history, and account settings.
- Allow users to update their full name and contact email.
- Support logout by destroying the session and redirecting to `index.php`.
- Add a remembered logged-in check on all protected pages (redirect to `login.php` if not authenticated).

## 3. Event listing and details

- Verify `event.php` shows event details correctly and is protected for authenticated users where needed.
- Add search and filter support for event categories, dates, and ticket types.
- Ensure `category.php` and `calendar.php` show matching results.
- Clean up event image loading and fallback handling.

## 4. Ticket purchase and checkout

- Confirm `seat_selection.php`, `process_seats.php`, `checkout.php`, `process_payment.php`, and `payment_success.php` all validate user/session state.
- Add server-side checks for available seat inventory before checkout.
- Validate the selected ticket count and price before creating bookings.
- Add order confirmation emails or PDF generation if needed.

## 5. Resale and secondary tickets

- Verify `resell_ticket.php`, `resale_listings.php`, and `buy_resale.php` update status safely.
- Prevent users from reselling already purchased or expired tickets.
- Ensure resale purchases update ticket ownership and remove stale listings.

## 6. Reviews and ratings

- Confirm `submit_review.php` only allows logged-in users to submit reviews.
- Add review moderation in `admin/manage_reviews.php` if required.
- Prevent duplicate reviews by the same user for the same event.

## 7. Admin section

- Audit `admin/events.php`, `admin/add_event.php`, and `admin/edit_event.php` for authentication and authorization.
- Make sure only admin users can access the admin directory.
- Add user role or admin flag in the `users` table if not already present.
- Add logging for admin changes and event updates.

## 8. Data and database tasks

- Review the full database schema and add missing indexes for email, event_id, and user_id fields.
- Add `auth_provider`, `google_id`, `created_at`, and `updated_at` columns to `users` if needed.
- Ensure `users.email` is unique and not nullable.
- Add referential integrity constraints between tickets, events, users, and resale listings if possible.

## 9. UI/UX and polish

- Add consistent messaging for success and error states across all forms.
- Improve mobile responsiveness in `assets/css/style.css`.
- Add a loading state for payment and checkout steps.
- Add clear navigation to login/register, dashboard, and ticket pages.

## 10. Testing and deployment

- [ ] Test registration, login, Google sign-in, and logout.
- [ ] Test event browsing, seat selection, checkout, and payment success.
- [ ] Test resale creation and resale purchase.
- [ ] Test admin event creation and review management.
- [ ] Confirm no PHP warnings or errors are logged during normal use.
- [ ] Confirm the app runs on the target local or production host.
- [ ] Document any required setup steps for XAMPP, database import, and Google OAuth.

## 11. Security improvements

- Add CSRF protection for all forms.
- Use HTTPS in production and enforce secure cookies.
- Sanitize all user input before DB and HTML output.
- Limit login attempts or add CAPTCHA for suspicious traffic.
- Avoid storing empty strings in password fields for social login users; use NULL instead.
