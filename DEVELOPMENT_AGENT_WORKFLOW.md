# Guest House Booking Platform Development Workflow

This document defines the step-by-step agent workflow for developing the Laravel + PostgreSQL guest house and banquet booking platform.

## Goal

Build the application module by module using a controlled loop:

1. Develop the feature.
2. Run tests.
3. Fix failures.
4. Run security checks.
5. Verify the feature.
6. Report progress.
7. Move to the next module.

## Agent Roles

### 1. Orchestrator

The main coordinator.

Responsibilities:

- Select the next module to build.
- Define the task scope.
- Prevent unrelated changes.
- Review results from developer, tester, fixer, and security review.
- Decide whether the module is complete.

### 2. Developer Agent

Builds the selected feature.

Responsibilities:

- Create migrations, models, controllers, routes, views, requests, services, and tests.
- Follow Laravel conventions.
- Use PostgreSQL-compatible schema design.
- Keep code modular and scalable.
- Avoid hard-coded business rules where settings should be dynamic.

### 3. Tester Agent

Validates the feature.

Responsibilities:

- Run Laravel tests.
- Check feature behavior.
- Test validation rules.
- Test database behavior.
- Identify broken routes, forms, UI states, or edge cases.

### 4. Fixer Agent

Fixes issues found by testing.

Responsibilities:

- Resolve failed tests.
- Fix broken UI or backend behavior.
- Re-run affected tests.
- Avoid unrelated refactoring.

### 5. Security Reviewer Agent

Checks the feature for security risks.

Responsibilities:

- Verify authentication and authorization.
- Check role access rules.
- Check CSRF protection.
- Check validation and sanitization.
- Check file upload safety.
- Check payment verification logic.
- Check rate limiting where required.
- Check customer data isolation.

### 6. Final Verifier

Confirms the module is ready.

Responsibilities:

- Confirm tests pass.
- Confirm no major security issue remains.
- Confirm feature works through browser/API.
- Summarize completed work.

## Module Build Order

### Module 1: Authentication And Roles

Build:

- Customer registration.
- Customer login/logout.
- Password reset.
- Admin login.
- Super Admin role.
- Property Manager role.
- Customer role.

Security checks:

- Rate-limited login.
- Password validation.
- Authenticated route protection.
- Customer/admin route separation.

### Module 2: Admin Panel Foundation

Build:

- Admin layout.
- Sidebar navigation.
- Dashboard shell.
- Protected `/admin` routes.

Dashboard widgets:

- Today bookings.
- Check-ins.
- Check-outs.
- Occupancy.
- Revenue.
- Pending payments.

### Module 3: Property Management

Build:

- Property CRUD.
- Property images.
- Address/location.
- Contact details.
- Amenities.
- Active/inactive status.

Security checks:

- Only authorized admins can manage properties.
- Validate image uploads.
- Prevent unauthorized property access.

### Module 4: Room Management

Build:

- Room type CRUD.
- Room CRUD.
- Capacity.
- Base price.
- Extra guest price.
- Room images.
- Maintenance status.

Security checks:

- Validate prices as integer minor units.
- Prevent managers from editing unassigned properties.

### Module 5: Availability Engine

Build:

- Search by property/location.
- Check-in/check-out date validation.
- Guest count handling.
- Room availability calculation.
- Date blocking.
- Double-booking prevention.

Security checks:

- Server-side availability verification.
- Prevent race-condition double booking.
- Validate date ranges.

### Module 6: Public Property And Room Pages

Build:

- Property listing page.
- Property details page.
- Room category display.
- Banquet display.
- Amenities and pricing display.
- Search form.

UX checks:

- Mobile responsive.
- Fast booking CTA.
- Clear pricing and availability.

### Module 7: Customer Booking Flow

Build:

- Select room.
- Enter guest details.
- Login/register during booking.
- Review booking.
- Create pending booking.
- Booking status lifecycle.

Statuses:

- Draft.
- Pending Payment.
- Confirmed.
- Cancelled.
- Checked In.
- Checked Out.
- No Show.

### Module 8: Payment Gateway

Build:

- Razorpay or selected gateway integration.
- Create payment order.
- Verify payment server-side.
- Store payment transaction.
- Confirm booking after successful payment.

Security checks:

- Never trust frontend payment success alone.
- Verify gateway signature/callback.
- Log payment events.

### Module 9: Customer Portal

Build:

- Customer profile.
- Booking history.
- Booking details.
- Payment status.

Security checks:

- Customers can only view their own bookings.
- Protect personal data.

### Module 10: Banquet Booking Module

Build:

- Banquet listing.
- Banquet packages.
- Event date inquiry.
- Admin approval.
- Banquet payment/advance support.
- Event notes.

Security checks:

- Prevent event date conflicts.
- Validate event data.

### Module 11: Notifications

Build:

- Booking confirmation email.
- Payment success email.
- Payment failure email.
- Admin new booking alert.
- Basic SMS integration.
- Notification logs.

Scalability checks:

- Queue email/SMS jobs.
- Retry failed jobs.

### Module 12: Reports

Build:

- Booking report.
- Occupancy report.
- Revenue report.
- Property-wise report.
- Date-wise report.
- Payment report.

Filters:

- Date range.
- Property.
- Booking status.
- Payment status.

### Module 13: Security Hardening

Implement:

- Audit logs.
- Rate limits.
- Secure headers.
- File upload restrictions.
- Authorization policies.
- Production `.env` checklist.
- `APP_DEBUG=false` production rule.

### Module 14: Deployment Readiness

Prepare:

- PostgreSQL production setup.
- SSL configuration.
- Queue worker.
- Laravel scheduler cron.
- Backup strategy.
- Log rotation.
- Error monitoring.

## Standard Execution Loop

Each module must follow this loop:

```text
1. Define exact module scope.
2. Build the smallest useful version.
3. Add migrations and models.
4. Add routes/controllers/views/services.
5. Add focused tests.
6. Run tests.
7. Fix failures.
8. Run security review.
9. Fix security issues.
10. Verify manually.
11. Mark module complete.
```

## Testing Checklist

For each module:

- Unit tests for business logic.
- Feature tests for web flows.
- Validation tests.
- Authorization tests.
- Regression test for existing working routes.

Core commands:

```bash
php artisan test
npm run build
composer validate --strict
```

## Security Checklist

For each module:

- Is the route protected if needed?
- Is the user authorized for this record?
- Are all inputs validated?
- Are uploads restricted?
- Are database queries safe?
- Is sensitive data hidden?
- Are payment callbacks verified?
- Are important actions logged?

## Development Rules

- Build one module at a time.
- Do not mix unrelated features.
- Do not skip tests.
- Do not confirm a booking without server-side payment verification.
- Do not expose customer data across accounts.
- Do not store secrets in code.
- Keep PostgreSQL constraints and indexes strong.
- Keep UI mobile-friendly.
- Keep business rules configurable where practical.

## Current Priority

Start with:

```text
Module 1: Authentication And Roles
```

After Module 1 is complete, continue module by module in the order listed above.
