// Core domain models for RJ Performance Horses

export interface Rider {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  dateOfBirth?: string;
  address?: string;
  emergencyContactName?: string;
  emergencyContactPhone?: string;
  experienceLevel: 'beginner' | 'intermediate' | 'advanced';
  notes?: string;
  createdAt: string;
  updatedAt: string;
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
  available: boolean;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface LessonType {
  id: string;
  name: string;
  description?: string;
  durationMinutes: number;
  price: number;
  difficultyLevel: 'beginner' | 'intermediate' | 'advanced';
  maxGroupSize: number;
  createdAt: string;
  updatedAt: string;
}

export interface TimeSlot {
  id: string;
  horseid: string;
  date: string; // YYYY-MM-DD
  startTime: string; // HH:MM
  endTime: string; // HH:MM
  status: 'available' | 'booked' | 'maintenance';
  createdAt: string;
  updatedAt: string;
}

export interface Booking {
  id: string;
  lessonTypeId: string;
  riderId: string;
  horseid: string;
  startTime: string; // ISO 8601 datetime
  endTime: string; // ISO 8601 datetime
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  bookingType?: 'regular' | 'trial' | 'makeup' | 'recurring';
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Waiver {
  id: string;
  riderId: string;
  bookingId?: string;
  waiverVersion: number;
  signedAt?: string;
  isSigned: boolean;
  signerName?: string;
  signerEmail?: string;
  relationshipToRider?: string;
  createdAt: string;
  updatedAt: string;
}

export interface AdminUser {
  id: string;
  email: string;
  name?: string;
  role: 'admin' | 'instructor' | 'staff';
  active: boolean;
  lastLogin?: string;
  createdAt: string;
  updatedAt: string;
}

export interface BookingWizardState {
  currentStep: number;
  selectedLessonType?: LessonType;
  selectedDate?: string;
  selectedTime?: string;
  riderInfo: Partial<Rider>;
  waiverSigned: boolean;
}
