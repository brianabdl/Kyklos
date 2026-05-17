# Kyklos — Workforce Clock-In / Clock-Out System

Kyklos is a digital attendance management system designed to make tracking work hours simple, accurate, and transparent — for both employees and managers.

---

## What It Does

Managing employee attendance with paper sheets or manual logs is slow and error-prone. Kyklos replaces that with a mobile-friendly system where employees can clock in and out from their phones, and managers get a real-time view of who is on the clock.

### For Employees

- **Clock in and out** from anywhere — with optional location check to confirm you're at the right site
- **Manage breaks** — start and end break periods during a shift, all tracked automatically
- **View your own history** — see your past shifts, total hours worked, and attendance stats at a glance
- **Stay informed** — receive push notifications for shift assignments and important updates

### For Managers

- **Live team overview** — see at a glance who is currently working, on break, or has clocked out
- **Shift management** — create and assign shifts to employees or entire work sites
- **Full audit trail** — every clock-in, break, and clock-out is recorded with a timestamp, so nothing is lost
- **Reports** — generate and save attendance reports for payroll, compliance, or review purposes

---

## How Access Works

Kyklos is built with security in mind. Getting in requires two steps:

1. **Sign in** with your email and password (or your Google / Apple account)
2. **Confirm your identity** with a personal PIN code

This two-step process means that even if someone else knows your password, they cannot access your account without the PIN.

Each organization's data is fully separated — employees and managers of one company can never see data from another.

---

## Getting Started

### Prerequisites

Before setting up the project, make sure you have the following installed on your machine:

- **PHP 8.3 or higher**
- **Composer** (PHP dependency manager)
- **Node.js & npm** (for frontend assets)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/brianabdl/kyklos-backend.git
cd kyklos-backend

# 2. Run the one-command setup
#    (installs dependencies, creates the database, and generates an app key)
composer setup

# 3. Start the development server
composer dev

# 4. In a separate terminal, start the Reverb & Queue workers
php artisan reverb:start
php artisan queue:work
```

The app will be available at `http://localhost:8000`.

### Configuration

The `.env.example` file includes sensible defaults for local development. The only values you need to set before starting are the Reverb credentials, which power real-time push notifications:

```env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

### Demo Accounts

After setup, the database is pre-loaded with demo accounts you can use to explore the system:

| Role | Email | Password |
|---|---|---|
| Manager | manager@demo.co | password |
| Employee | employee@demo.co | password |

> PIN for all demo accounts: **1234**

---

## Who It's For

Kyklos is built for organizations of any size that need a straightforward, reliable way to track employee attendance — whether it's a single site or multiple locations with different teams.

---

## License

MIT
