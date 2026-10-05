# Development Guide - RJ Performance Horses

## Project Setup

### Prerequisites
- Node.js 16+ and npm
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/rjperformancehorses.git
cd rjperformancehorses
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run dev
```

The application will open at `http://localhost:5173`

## Development Commands

### Run Development Server
```bash
npm run dev
```
Starts the Vite development server with hot module replacement.

### Build for Production
```bash
npm run build
```
Creates an optimized production build in the `dist/` folder.

### Preview Production Build
```bash
npm run preview
```
Locally preview the production build.

### Lint and Format
```bash
npm run lint
```
Runs ESLint to check code quality.

## Project Structure

```
src/
├── pages/
│   ├── public/          # Customer-facing pages
│   │   ├── HomePage.vue
│   │   ├── AboutPage.vue
│   │   ├── LessonsPage.vue
│   │   ├── PricingPage.vue
│   │   ├── FAQPage.vue
│   │   ├── ContactPage.vue
│   │   └── BookingWizardPage.vue
│   └── admin/           # Admin dashboard pages
│       ├── LoginPage.vue
│       ├── DashboardPage.vue
│       ├── RidersPage.vue
│       ├── HorsesPage.vue
│       ├── BookingsPage.vue
│       └── AvailabilityPage.vue
├── components/
│   ├── layouts/         # Layout wrappers
│   ├── booking/         # Booking wizard components
│   ├── admin/           # Admin page components
│   └── common/          # Shared UI components (TODO)
├── stores/              # Pinia state management
│   ├── bookingStore.ts
│   ├── adminStore.ts
│   └── authStore.ts
├── types/               # TypeScript interfaces
│   └── models.ts
├── data/                # Mock data and fixtures
│   └── mockData.ts
├── styles/              # Global CSS
│   ├── main.css
│   └── variables.css
├── router/              # Vue Router configuration
│   └── index.ts
└── main.ts              # Application entry point
```

## Demo Credentials

For testing the admin panel:
- **Email:** `admin@rjhorses.com`
- **Password:** `demo123`

## Data & State Management

### Phase 1 Data Storage
- **In-Memory:** Pinia stores manage all application state
- **Local Storage:** Authentication tokens persisted to localStorage
- **Mock Data:** Seed data provided in `src/data/mockData.ts`

### Stores
1. **bookingStore** - Manages booking wizard state and form data
2. **adminStore** - Manages admin data (riders, horses, bookings, availability)
3. **authStore** - Manages authentication state and login

## Styling

The project uses:
- **Tailwind CSS** for utility classes
- **CSS Variables** for design system values (colors, spacing, etc.)
- **BEM naming** for custom component styles

### Design System
Colors are defined in `src/styles/variables.css`:
- Primary: Cream background (#F5F1ED)
- Text: Dark brown (#3D2817)
- Accent: Warm gold (#D4A574)
- Borders: Light brown (#E8DCC8)

## Routing

Routes are configured in `src/router/index.ts`:

### Public Routes
- `/` - Home
- `/about` - About Rylie
- `/lessons` - Lesson programs
- `/pricing` - Pricing page
- `/faq` - FAQ
- `/contact` - Contact form
- `/book` - Booking wizard

### Admin Routes (Protected)
- `/admin/login` - Login page
- `/admin/dashboard` - Dashboard overview
- `/admin/riders` - Manage riders
- `/admin/horses` - Manage horses
- `/admin/bookings` - View/manage bookings
- `/admin/availability` - Set availability and blocked times

## Browser Support

Target browsers:
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Modern mobile browsers

## Next Steps / Phase 2

1. **Backend API** - Connect to PostgreSQL/Supabase
2. **Authentication** - Implement real JWT-based auth
3. **Email Integration** - Send confirmation emails and waivers
4. **Calendar Sync** - Integrate with Google Calendar or iCloud
5. **Payment Processing** - Add Stripe or similar
6. **Enhanced Admin** - More detailed reporting and analytics
7. **Mobile App** - Consider native mobile application

## Troubleshooting

### Port Already in Use
If port 5173 is in use:
```bash
npm run dev -- --port 3000
```

### Node Modules Issues
Clear and reinstall:
```bash
rm -rf node_modules package-lock.json
npm install
```

### Type Checking Errors
Run type checker:
```bash
npx vue-tsc --noEmit
```

## Resources

- [Vue 3 Documentation](https://vuejs.org)
- [Vite Documentation](https://vitejs.dev)
- [Pinia Documentation](https://pinia.vuejs.org)
- [Vue Router Documentation](https://router.vuejs.org)
- [Tailwind CSS Documentation](https://tailwindcss.com)
