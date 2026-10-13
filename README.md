# RJ Performance Horses

Professional website and booking system for RJ Performance Horses riding lesson business.

## Phase 1 Status

✅ **Completed**
- Professional responsive homepage with hero section
- Lessons and pricing pages with service descriptions
- About Rylie section with placeholder biography
- FAQ with collapsible questions
- Contact form (mocked - no backend submission yet)
- Multi-step booking wizard with live availability checking
- Admin dashboard with authentication
- Admin pages for managing riders, horses, and bookings
- Availability/calendar management interface
- PostgreSQL schema design (ready for Phase 2 backend)
- Mock data with 6 horses, 5 riders, 8 lesson types

❌ **Not Implemented (Phase 2+)**
- Backend API and database
- Real authentication and password hashing
- Email confirmations and notifications
- Payment processing
- Calendar OAuth/synchronization
- PDF waiver generation and storage
- Makeup credit tracking
- Recurring lesson scheduling

## Quick Start

### Install Dependencies
```bash
npm install
```

### Run Development Server
```bash
npm run dev
```

Opens at `http://localhost:5173`

### Build for Production
```bash
npm run build
```

Creates optimized static build in `dist/` folder ready for GitHub Pages, Vercel, or Netlify.

## Admin Access

**Demo Credentials:**
- Email: `admin@rjhorses.com`
- Password: `demo123`

Navigate to `/admin/login` to access the dashboard.

## Project Structure

```
src/
├── pages/
│   ├── public/          # Customer-facing pages
│   └── admin/           # Admin dashboard pages
├── components/
│   ├── layouts/         # Page layouts
│   ├── booking/         # Booking wizard
│   └── common/          # Shared UI components
├── stores/              # Pinia state management
├── types/               # TypeScript interfaces
├── data/                # Mock data
├── styles/              # Global CSS and design system
└── router/              # Vue Router configuration
```

## Tech Stack

- **Framework:** Vue 3
- **Build Tool:** Vite
- **State Management:** Pinia
- **Routing:** Vue Router
- **Styling:** Tailwind CSS + CSS Variables
- **Language:** TypeScript
- **Data:** In-memory stores + localStorage (Phase 1)

## Design System

Modern western aesthetic with:
- Cream backgrounds (#F5F1ED)
- Dark brown text (#3D2817)
- Warm gold accents (#D4A574)
- Light brown borders (#E8DCC8)
- Professional serif typography (Georgia, Garamond)

## Production Architecture

### Frontend (Static Hosting)
```bash
npm run build
# Deploy dist/ to GitHub Pages, Vercel, Netlify, or S3+CloudFront
```

### Backend
```
MySQL
    ↕
Laravel API (api/, served at /api)
    ↕
Frontend SPA (this repo)
```

- **Database:** MySQL 8
- **Server:** Laravel 13 on Nginx + PHP-FPM (IONOS VPS)
- **Authentication:** Laravel Sanctum (session cookies)
- See [MIGRATION_PLAN.md](./MIGRATION_PLAN.md)
- **External Services:** SendGrid (email), Stripe (payments), Google OAuth (calendar)

## Documentation

- [DEVELOPMENT.md](./DEVELOPMENT.md) - Setup and development guide
- [ARCHITECTURE.md](./ARCHITECTURE.md) - System design and technical decisions
- [database/schema.sql](./database/schema.sql) - PostgreSQL schema for Phase 2+

## Key Features

### Public Website
- **Home Page** - Hero section with call-to-action
- **Lessons** - Detailed lesson descriptions and types
- **Pricing** - Transparent pricing for all services
- **About** - Information about Rylie and teaching philosophy
- **FAQ** - Common questions and answers
- **Contact** - Contact form and information
- **Responsive Design** - Mobile, tablet, and desktop optimized

### Booking System
- **5-Step Wizard**
  1. Select lesson type (with pricing)
  2. Choose date and available time
  3. Enter rider information
  4. Review and sign waiver
  5. Confirm booking

- **Availability Checking** - Shows available time slots for selected date
- **Responsive Form** - Works on all devices
- **Data Persistence** - Survives page navigation

### Admin Dashboard
- **Authentication** - Demo login system
- **Dashboard** - Overview with today's lessons and upcoming bookings
- **Rider Management** - Add, edit, delete rider information
- **Horse Management** - Manage available horses and their abilities
- **Booking Management** - View and update booking statuses
- **Availability Settings** - Block time and manage schedules
- **Mobile Support** - Responsive admin interface

## Data Model (Phase 1 Mock)

- **Lesson Types** - 8 types (Walk & Trot, Barrel Racing, etc.)
- **Horses** - 6 horses with breeds, ages, and experience levels
- **Riders** - 5 sample riders with contact info
- **Time Slots** - 30 days of generated availability
- **Bookings** - Sample booking records
- **Availability** - Mock calendar with available/blocked times

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Modern mobile browsers

## What's Next? Phase 2 Recommendations

1. **Backend API** - Laravel API with MySQL (see MIGRATION_PLAN.md)
   - Admin authentication with Sanctum
   - CRUD endpoints for all resources
   - Availability calculation logic
   - Booking confirmation workflow

2. **Email Integration** - Send confirmations and waivers
   - Booking confirmations
   - Waiver reminders
   - Calendar sync notifications

3. **Payment Processing** - Integrate Stripe
   - Accept card payments
   - Handle refunds
   - Send invoices

4. **Calendar Synchronization** - Connect to external calendars
   - Google Calendar OAuth
   - iCloud integration
   - Two-way sync of confirmed bookings

5. **PDF Generation** - Create downloadable waivers
   - Store signed waivers
   - Email copies to customers
   - Audit trail of acceptances

6. **Enhanced Admin Features**
   - Advanced reporting and analytics
   - Recurring lesson scheduling
   - Makeup credit tracking
   - Instructor availability rules

## License

[Add appropriate license]

## Contact

For questions about this codebase, contact the development team.

---

**Note:** This is Phase 1 of the RJ Performance Horses booking system. All data is mocked and stored in the browser. Backend integration and database features come in Phase 2.
