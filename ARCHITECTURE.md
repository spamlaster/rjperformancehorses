# Architecture Overview - RJ Performance Horses

## System Design

### Phase 1: Prototype with Frontend Only

**Frontend:** Vue 3 + TypeScript + Vite  
**State Management:** Pinia  
**Data Storage:** In-memory + localStorage  
**Styling:** Tailwind CSS + CSS Variables  
**Hosting:** Static site (GitHub Pages, Vercel, Netlify)

```
┌─────────────────────────────────────────────┐
│         Public Website & Admin Panel         │
│  (Vue 3 SPA - Static HTML/JS/CSS/Assets)    │
│                                             │
│  ├─ Public Pages (Marketing)                │
│  │  ├─ Home, About, Lessons, Pricing       │
│  │  ├─ FAQ, Contact                         │
│  │  └─ Booking Wizard                       │
│  │                                          │
│  ├─ Admin Dashboard (Protected)             │
│  │  ├─ Dashboard                            │
│  │  ├─ Rider Management                    │
│  │  ├─ Horse Management                    │
│  │  ├─ Booking Management                  │
│  │  └─ Availability Settings                │
│  │                                          │
│  └─ State Management (Pinia)                │
│     ├─ bookingStore                         │
│     ├─ adminStore                           │
│     └─ authStore                            │
│                                             │
└─────────────────────────────────────────────┘
        localStorage (Auth Token)
```

### Phase 2+: Backend Integration

**Backend:** Node.js/Express + PostgreSQL or Supabase  
**API:** REST or GraphQL  
**Authentication:** JWT  
**External Services:** Email, Calendar OAuth, Payments

```
┌──────────────────────────┐       ┌──────────────────────────┐
│   Frontend (Same as P1)   │◄────►│  Backend API (Node/Expr)  │
│   Vue 3 SPA               │       │  PostgreSQL Database      │
│   Deployed to CDN/Pages   │       │  Deployed to Cloud        │
└──────────────────────────┘       └──────────────────────────┘
                                            │
                                 ┌──────────┼──────────┐
                                 ▼          ▼          ▼
                            Supabase   SendGrid    Google OAuth
                            (or DB)    (Email)     (Calendar)
```

## Data Model (Post-Pre-Phase-2 Cleanup)

### Core Entities

**Customers** (Account Holders)
- Parent/guardian who books lessons
- Can have multiple rider children
- Adult customers may also be riders
- Auth-ready: `auth_user_id` for Supabase integration

**Riders** (Lesson Participants)
- Person actually riding the horse
- Belongs to a customer (FK)
- One rider per customer (adult self-booking)
- Multiple riders per customer (parent + children)

**Horses** (Scheduling Resources)
- Not necessarily owned by Rylie
- Can be boarded/shared (tracked via horse_owners table)
- Status: active/inactive (not just available/booked)
- Lesson eligibility defined via horse_lesson_types

**Lesson Types** (Products/Services)
- Immutable pricing and duration
- Difficulty levels and group size limits
- Active flag for retiring lessons

**Availability Model**
- **Recurring base**: instructor_availability, horse_recurring_availability (weekly schedules)
- **Exceptions**: blocked_times table (date-specific overrides)
- **Bookings**: Individual occurrences, checked for conflicts

**Bookings** (Scheduled Lessons)
- Links customer, lesson type, horse, riders
- Status: pending, confirmed, completed, cancelled, no_show
- Version column for Phase 2 optimistic locking
- Recurring lesson reference for scheduling rules

**Waivers**
- Versioned documents (active/inactive)
- Acceptances are immutable audit records
- Preserve exact version signed by customer
- Link to booking for audit trail

**Makeup Credits**
- Issued from cancelled bookings
- Track expiration and redemption
- Redeemed via separate booking

---

## Key Architectural Decisions

### 2. No Admin User Table
**Decision:** Remove admin_users table. Use Supabase Auth for admin authentication.

**Rationale:**
- Never store passwords in application tables
- Supabase Auth handles secure hashing and session management
- RLS policies determine admin access based on auth.users.id
- Cleaner separation: auth = Supabase, authorization = RLS

### 3. Customer-Rider Separation
**Decision:** Separate customers (account holders) from riders (lesson participants).

**Rationale:**
- Parent/guardian books lessons for children
- Adult can book for themselves (same person in both tables)
- Siblings share one customer but are separate riders
- Supports sibling lessons (two riders, one customer)
- Future: group lesson pricing per customer

### 4. Recurring Availability vs Exceptions
**Decision:** Separate normal schedules from date-specific blocks.

**Rationale:**
- Instructor and horses have weekly recurring patterns
- Special closures, vet appointments, competitions are exceptions
- Queries check recurring schedules first, then blocked_times
- Cleaner model than generating/maintaining daily slots

### 5. Booking Centrality
**Decision:** Bookings tie together all resources.

**Rationale:**
- Single source of truth for lesson occurrences
- Booking = (customer, lesson_type, horse, riders, time_window)
- booking_riders table handles 1-to-many (group lessons)
- recurring_lesson_id links to rules without duplicating logic

### 1. Static Frontend Hosting
**Decision:** Build frontend as a standalone SPA deployable to static hosting (GitHub Pages, Vercel, Netlify).

**Rationale:**
- Lower infrastructure costs
- Easier horizontal scaling
- Better performance (CDN distribution)
- Separates frontend from backend concerns

**Implications:**
- Backend API must be decoupled
- All sensitive operations happen server-side
- Authentication uses tokens (JWT via Supabase)

### 2. Type-Safe with TypeScript
**Decision:** Use TypeScript throughout the codebase.

**Rationale:**
- Catch errors at compile time
- Better IDE support and developer experience
- Easier refactoring
- Self-documenting code

**Type Organization:**
- `types/models.ts` - Domain models (Rider, Horse, Booking, etc.)
- `types/api.ts` - API request/response types (future)

### 3. Pinia for State Management
**Decision:** Use Pinia (successor to Vuex) for state management.

**Rationale:**
- Simpler, more intuitive API than Vuex
- Better TypeScript support
- Less boilerplate
- Excellent for medium-sized applications

**Stores:**
- `bookingStore` - Booking wizard state (temporary)
- `adminStore` - Admin data (riders, horses, bookings)
- `authStore` - Authentication state

### 4. Tailwind + CSS Variables Design System
**Decision:** Combine Tailwind CSS utilities with CSS custom properties for design system.

**Rationale:**
- Utility-first development is fast
- CSS variables allow global design system changes
- Easy dark mode support
- Consistent spacing, colors, typography

**Design System Files:**
- `styles/variables.css` - CSS custom properties
- `tailwind.config.js` - Tailwind configuration
- Component-specific styles in `.vue` files

### 5. Mocked Data in Phase 1
**Decision:** Use fully mocked data with in-memory stores and localStorage for Phase 1.

**Rationale:**
- Eliminates backend dependency for prototype
- Fast iteration on UI/UX
- Data persists in browser (localStorage)
- Easy to wire into real backend later

**Mock Data Strategy:**
- Seed data in `data/mockData.ts`
- Time slots generated on app load
- All CRUD operations on in-memory stores
- No API calls in Phase 1

### 6. Client-Side Route Guards
**Decision:** Use Vue Router meta and beforeEach guards for admin access control.

**Rationale:**
- Simple demo authentication
- Real auth will be JWT-based in Phase 2
- Guards redirect to login page
- Token stored in localStorage

## Data Flow

### Booking Wizard Flow
```
1. BookingWizardPage mounted
   ↓
2. User selects lesson type
   → bookingStore.setLessonType()
   ↓
3. User selects date & time
   → bookingStore.setDateAndTime()
   → adminStore.getAvailableSlots() [query]
   ↓
4. User enters rider info
   → bookingStore.updateRiderInfo()
   ↓
5. User accepts waiver
   → bookingStore.setWaiverSigned()
   ↓
6. User confirms
   → bookingStore.submitBooking()
   → adminStore.addBooking() [create]
   → console.log() [Phase 1 demo]
```

### Admin Data Flow
```
Dashboard loads
   ↓
useAdminStore() provides:
   ├─ adminStore.riders (read)
   ├─ adminStore.horses (read)
   ├─ adminStore.bookings (read)
   ├─ adminStore.getUpcomingBookings() (query)
   └─ adminStore.getAvailableSlots() (query)
   
User action:
   → adminStore.addRider() / updateRider() / deleteRider()
   → adminStore.addHorse() / updateHorse() / deleteHorse()
   → adminStore.addBooking() / updateBooking()
   → Store updates in-memory arrays
   → Component re-renders (Vue reactivity)
```

## Component Hierarchy

### Public Site
```
App.vue
├─ PublicLayout
│  ├─ Navigation
│  │  └─ Router Links
│  ├─ Slot (Page Content)
│  └─ Footer
└─ Pages:
   ├─ HomePage (Hero, Features, CTA)
   ├─ AboutPage (Bio, Philosophy)
   ├─ LessonsPage (Lesson Cards)
   ├─ PricingPage (Pricing Cards)
   ├─ FAQPage (FAQ Accordion)
   ├─ ContactPage (Contact Form)
   └─ BookingWizardPage
      └─ BookingWizard (Multi-step form)
         ├─ LessonTypeStep
         ├─ DateTimeStep
         ├─ RiderInfoStep
         ├─ WaiverStep
         └─ ConfirmationStep
```

### Admin Interface
```
App.vue
├─ AdminLayout
│  ├─ AdminSidebar (Navigation)
│  ├─ Slot (Page Content)
│  └─ Logout Button
└─ Pages:
   ├─ LoginPage (Auth)
   ├─ DashboardPage (Overview)
   ├─ RidersPage (CRUD Table)
   ├─ HorsesPage (CRUD Table)
   ├─ BookingsPage (View/Update)
   └─ AvailabilityPage (Calendar/Slots)
```

## Separation of Concerns

### Pages Layer
- Route-level components
- Coordinate layout and page-specific logic
- Import layouts and child components

### Components Layer
- Reusable UI components
- Layouts (PublicLayout, AdminLayout)
- Forms (RiderForm, HorseForm)
- Tables and lists
- Cards, buttons, inputs (atoms)

### Stores Layer (State Management)
- Business logic
- Data mutations
- Query methods
- No side effects

### Utils Layer
- Pure functions
- Formatters (dates, currency)
- Validators
- Helpers

### Styles Layer
- Global CSS and variables
- Component-scoped styles in `.vue` files
- Design system consistency

## Testing Strategy (Phase 2+)

- **Unit Tests:** Stores, utilities, validators
- **Component Tests:** Individual Vue components
- **E2E Tests:** User workflows (booking, admin operations)
- **Integration Tests:** Store + component interaction

## Security Considerations

### Phase 1 (Current)
- **No sensitive data** - demo credentials only
- **No real payments** - booking is mocked
- **Browser storage only** - localStorage not secure for production
- **Client-side validation** - convenience only, not security

### Phase 2+ (Backend)
- **JWT Authentication** - stateless, secure tokens
- **HTTPS Only** - encrypted transit
- **CORS** - restrict API access
- **Input Validation** - server-side, not just client
- **Rate Limiting** - prevent abuse
- **Waiver Versioning** - immutable record of what was signed
- **PCI Compliance** - if processing payments (use Stripe)
- **Data Encryption** - sensitive data at rest

## Deployment

### Static Site (Current)
```bash
npm run build
# dist/ folder ready for GitHub Pages, Vercel, Netlify
```

### Future with Backend
- Frontend: `npm run build` → deploy dist/ to CDN
- Backend: Deploy Node.js app to Heroku, AWS, DigitalOcean, etc.
- Database: Provision PostgreSQL or use Supabase
- Email: SendGrid or similar service

## Performance Considerations

### Frontend
- Lazy-load route components (Vue Router)
- Tree-shake unused code (Vite)
- Minify and compress assets (Vite build)
- Optimize images (future)

### Backend (Phase 2+)
- Index database columns (see schema.sql)
- Pagination for large datasets
- Caching (Redis for session/availability)
- Connection pooling

## Scalability

### Phase 1-2
- Single instructor (Rylie)
- ~100-500 active riders
- No complex concurrency issues

### Phase 3+ (Growth)
- Multiple instructors
- Distributed scheduling
- Calendar sync across systems
- Analytics and reporting

## Known Limitations & Future Work

### Phase 1 Limitations
- No backend/database
- No real authentication (demo only)
- No email integration
- No payment processing
- No calendar synchronization
- All data lost on page refresh (unless bookmarks localStorage)

### Phase 2 Goals
- Complete backend API
- Real JWT authentication
- Email confirmations and waivers
- Stripe payment integration
- Google Calendar sync
- Enhanced admin reporting

### Phase 3+ Goals
- Mobile app (React Native or Flutter)
- Multiple instructors
- Advanced analytics
- Automated scheduling
- Video lesson library
- Student progress tracking
