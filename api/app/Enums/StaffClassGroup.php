<?php

namespace App\Enums;

enum StaffClassGroup: string
{
    case Management = 'management';
    case CollegeFaculty = 'college_faculty';
    case SchoolFaculty = 'school_faculty';
    case Visiting = 'visiting';
    case Pti = 'pti';
    case TeachingAssistant = 'teaching_assistant';
    case Supporting = 'supporting';
}
