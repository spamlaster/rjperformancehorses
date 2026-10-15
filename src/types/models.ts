// Core domain models for RJ Performance Horses

export interface Customer {
  id: string;
  authUserId?: string; // users.id of a linked login (Phase 2 accounts)
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  createdAt: string;
  updatedAt: string;
  deletedAt?: string;
}

export interface Rider {
  id: string;
  customerId?: string; // FK to customer (nullable for migration)
  firstName: string;
  lastName: string;
  email?: string;
  phone?: string;
  dateOfBirth?: string;
  address?: string;
  emergencyContactName?: string;
  emergencyContactPhone?: string;
  experienceLevel: 'beginner' | 'intermediate' | 'advanced';
  notes?: string;
  createdAt: string;
  updatedAt: string;
  deletedAt?: string;
}

export interface Horse {
  id: string;
  name: string;
  breed?: string;
  age?: number;
  color?: string;
  height?: string;
  temperament?: string;
  experienceLevel: 'beginner_friendly' | 'intermediate' | 'advanced' | 'competition';
  active: boolean; // Renamed from 'available' for clarity
  notes?: string;
  createdAt: string;
  updatedAt: string;
  deletedAt?: string;
}

export interface LessonType {
  active: boolean;
  id: string;
  name: string;
  description?: string;
  durationMinutes: number;
  price: number;
  difficultyLevel: 'beginner' | 'intermediate' | 'advanced';
  maxGroupSize: number;
  requiredHorseCount: number;
  requiredRiderCount: number;
  createdAt: string;
  updatedAt: string;
}

export interface InstructorAvailability {
  id: string;
  dayOfWeek: number; // 0=Sunday, 1=Monday, ..., 6=Saturday
  startTime: string; // HH:MM
  endTime: string; // HH:MM
  active: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface HorseRecurringAvailability {
  id: string;
  horseId: string;
  dayOfWeek: number;
  startTime: string; // HH:MM
  endTime: string; // HH:MM
  active: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface BlockedTime {
  id: string;
  horseId?: string; // NULL for instructor blocks
  date: string; // YYYY-MM-DD
  startTime: string; // HH:MM
  endTime: string; // HH:MM
  reason?: string; // "Vet appointment", "Farrier", etc.
  createdAt: string;
  updatedAt: string;
}

export interface Booking {
  id: string;
  customerId: string;
  lessonTypeId: string;
  horseId: string; // Primary horse (legacy compat)
  startTime: string; // ISO 8601 datetime
  endTime: string; // ISO 8601 datetime
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
  bookingType: 'regular' | 'trial' | 'makeup' | 'recurring';
  recurringLessonId?: string; // FK to recurring_lessons
  version?: number; // For optimistic locking (Phase 2F)
  notes?: string;
  createdAt: string;
  updatedAt: string;
  deletedAt?: string;
  waiverCount?: number;
  riderNames?: string[]; // From booking_riders (admin reads only)
}

export interface BookingHorse {
  id: string;
  bookingId: string;
  horseId: string;
  createdAt: string;
}

export interface WaiverVersion {
  id: string;
  title: string;
  version: number;
  content: string;
  effectiveDate: string; // YYYY-MM-DD
  active: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface WaiverAcceptance {
  id: string;
  waiverId: string;
  customerId: string;
  riderId: string;
  bookingId?: string; // FK to booking that required this waiver
  signerName: string;
  signerEmail?: string;
  relationshipToRider?: string; // "parent", "guardian", "self", etc.
  acceptedAt: string; // ISO 8601 timestamp
  signatureData?: string; // Base64 encoded image
  notes?: string;
  createdAt: string;
  // No updatedAt: immutable record
}

export interface BookingWizardState {
  currentStep: number;
  selectedLessonType?: LessonType;
  selectedDate?: string;
  selectedTime?: string;
  riderInfo: Partial<Rider>;
  waiverSigned: boolean;
}

// Note: Admin authentication types live in stores/authStore.ts
// Do not create authentication-related types here
