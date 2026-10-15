import type { LessonType } from '@/types/models'

export interface LessonTemplate {
  key: string
  values: Pick<LessonType, 'name' | 'description' | 'durationMinutes' | 'price' | 'difficultyLevel' | 'requiredRiderCount' | 'requiredHorseCount' | 'maxGroupSize'>
}

export const lessonTemplates: LessonTemplate[] = [
  {
    key: 'private-30',
    values: {
      name: '30 Minute Private',
      description: 'Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.',
      durationMinutes: 30, price: 45, difficultyLevel: 'beginner',
      requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1,
    },
  },
  {
    key: 'private-45',
    values: {
      name: '45 Minute Private',
      description: 'Build on fundamentals with canter and basic jumping techniques.',
      durationMinutes: 45, price: 55, difficultyLevel: 'intermediate',
      requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1,
    },
  },
  {
    key: 'private-60',
    values: {
      name: '60 Minute Private',
      description: 'Advanced techniques and competition preparation for experienced riders.',
      durationMinutes: 60, price: 65, difficultyLevel: 'advanced',
      requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1,
    },
  },
  {
    key: 'group-60',
    values: {
      name: 'Group Lesson (60 min)',
      description: 'Two riders learning together on separate horses. Perfect for siblings or friends.',
      durationMinutes: 60, price: 100, difficultyLevel: 'beginner',
      requiredRiderCount: 2, requiredHorseCount: 2, maxGroupSize: 2,
    },
  },
  {
    key: 'barrel-60',
    values: {
      name: 'Barrel Racing (60 min)',
      description: 'Learn proper barrel racing techniques and speed control.',
      durationMinutes: 60, price: 75, difficultyLevel: 'intermediate',
      requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1,
    },
  },
  {
    key: 'barrel-90',
    values: {
      name: 'Barrel Racing (90 min)',
      description: 'Extended session for deeper practice and refinement.',
      durationMinutes: 90, price: 100, difficultyLevel: 'intermediate',
      requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1,
    },
  },
]
