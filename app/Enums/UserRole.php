<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case FieldSupervisor = 'field_supervisor';
    case AcademicSupervisor = 'academic_supervisor';
    case Coordinator = 'coordinator';

    /** Arabic label for the interface. */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'طالب',
            self::FieldSupervisor => 'المشرف الميداني',
            self::AcademicSupervisor => 'المشرف الأكاديمي',
            self::Coordinator => 'منسق التدريب',
        };
    }
}
