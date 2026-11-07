<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\IndustrialProjectGroup;
use App\Models\ProjectTitle;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AssessmentCriteriaSeeder::class,
        ]);

        // ── Academic Year ──────────────────────────────────────────────
        $academicYear = AcademicYear::firstOrCreate(
            ['year' => '2016/2017', 'semester' => 'Semester I'],
            ['is_active' => true]
        );

        // ── Department ─────────────────────────────────────────────────
        $department = Department::firstOrCreate(
            ['name' => 'School of Information Science'],
            [
                'office_location' => 'Main Campus, Block B, Room 201',
                'email'           => 'sis@wsu.edu.et',
                'phone'           => '+251 465 514 455',
            ]
        );

        // ══════════════════════════════════════════════════════════════
        //  ADMINISTRATOR
        // ══════════════════════════════════════════════════════════════
        User::firstOrCreate(
            ['email' => 'admin@wsu.edu.et'],
            [
                'first_name' => 'System',
                'last_name'  => 'Administrator',
                'id_number'  => 'STF/0001/14',
                'program'    => 'Staff',
                'role'       => 'Administrator',
                'salutation' => '',
                'password'   => 'admin123',
                'is_active'  => true,
            ]
        );

        // ══════════════════════════════════════════════════════════════
        //  DEPARTMENT HEAD — Arba Asha
        // ══════════════════════════════════════════════════════════════
        $deptHead = User::firstOrCreate(
            ['email' => 'arbaasha@gmail.com'],
            [
                'first_name' => 'Arba',
                'last_name'  => 'Asha',
                'id_number'  => 'STF/0002/14',
                'program'    => 'Staff',
                'role'       => 'DepartmentHead',
                'salutation' => 'Dr.',
                'gender'     => 'Male',
                'password'   => 'arba123',
                'is_active'  => true,
            ]
        );
        $department->update([
            'head_id'   => $deptHead->id,
            'head_name' => 'Dr. Arba Asha',
        ]);

        // ══════════════════════════════════════════════════════════════
        //  UG PROGRAM COORDINATOR — Temesgen T
        // ══════════════════════════════════════════════════════════════
        $ugpc = User::firstOrCreate(
            ['email' => 'temesgent@gmail.com'],
            [
                'first_name' => 'Temesgen',
                'last_name'  => 'T.',
                'id_number'  => 'STF/0003/14',
                'program'    => 'Staff',
                'role'       => 'UGPC',
                'salutation' => 'Mr.',
                'gender'     => 'Male',
                'password'   => 'temes123',
                'is_active'  => true,
            ]
        );

        // ══════════════════════════════════════════════════════════════
        //  GRADUATE PROGRAM COORDINATOR — Tekle Dargaso
        // ══════════════════════════════════════════════════════════════
        $gpc = User::firstOrCreate(
            ['email' => 'tekledargaso@gmail.com'],
            [
                'first_name' => 'Tekle',
                'last_name'  => 'Dargaso',
                'id_number'  => 'STF/0004/14',
                'program'    => 'Staff',
                'role'       => 'GPC',
                'salutation' => 'Dr.',
                'gender'     => 'Male',
                'password'   => 'tekle123',
                'is_active'  => true,
            ]
        );

        // ══════════════════════════════════════════════════════════════
        //  SCHOOL GRADUATE COMMITTEE (SGC)
        //  1. Melaku Bayih   2. Paulos Bogale   3. Wuletaw Eyasu
        // ══════════════════════════════════════════════════════════════
        $sgcData = [
            [
                'first_name' => 'Melaku',
                'last_name'  => 'Bayih',
                'email'      => 'melakubayih@gmail.com',
                'id_number'  => 'STF/0005/14',
                'gender'     => 'Male',
                'password'   => 'melak123',
            ],
            [
                'first_name' => 'Paulos',
                'last_name'  => 'Bogale',
                'email'      => 'paulosbogale@gmail.com',
                'id_number'  => 'STF/0006/14',
                'gender'     => 'Male',
                'password'   => 'paulo123',
            ],
            [
                'first_name' => 'Wuletaw',
                'last_name'  => 'Eyasu',
                'email'      => 'wuletaweyasu@gmail.com',
                'id_number'  => 'STF/0007/14',
                'gender'     => 'Male',
                'password'   => 'wulet123',
            ],
        ];
        foreach ($sgcData as $d) {
            User::firstOrCreate(
                ['email' => $d['email']],
                array_merge($d, [
                    'program'    => 'Staff',
                    'role'       => 'SGC',
                    'salutation' => 'Dr.',
                    'is_active'  => true,
                ])
            );
        }

        // ══════════════════════════════════════════════════════════════
        //  INDUSTRIAL PROJECT COMMITTEE (IPC)
        //  1. Sewunet Samuel   2. Fetenech Meskele   3. Netsanet Getachew
        // ══════════════════════════════════════════════════════════════
        $ipcData = [
            [
                'first_name' => 'Sewunet',
                'last_name'  => 'Samuel',
                'email'      => 'sewunetsamuel@gmail.com',
                'id_number'  => 'STF/0008/14',
                'gender'     => 'Male',
                'password'   => 'sewun123',
            ],
            [
                'first_name' => 'Fetenech',
                'last_name'  => 'Meskele',
                'email'      => 'fetenechmeskele@gmail.com',
                'id_number'  => 'STF/0009/14',
                'gender'     => 'Female',
                'password'   => 'feten123',
            ],
            [
                'first_name' => 'Netsanet',
                'last_name'  => 'Getachew',
                'email'      => 'netsanetgetachew@gmail.com',
                'id_number'  => 'STF/0010/14',
                'gender'     => 'Female',
                'password'   => 'netsa123',
            ],
        ];
        foreach ($ipcData as $d) {
            User::firstOrCreate(
                ['email' => $d['email']],
                array_merge($d, [
                    'program'    => 'Staff',
                    'role'       => 'IPC',
                    'salutation' => 'Mr.',
                    'is_active'  => true,
                ])
            );
        }

        // ══════════════════════════════════════════════════════════════
        //  ADVISORS
        //  1. Tekalign B.   2. Dawit Uta   3. Feven Teferi
        // ══════════════════════════════════════════════════════════════
        $advisorData = [
            [
                'first_name' => 'Tekalign',
                'last_name'  => 'B.',
                'email'      => 'tekalignb@gmail.com',
                'id_number'  => 'STF/0011/14',
                'gender'     => 'Male',
                'salutation' => 'Mr.',
                'password'   => 'tekal123',
            ],
            [
                'first_name' => 'Dawit',
                'last_name'  => 'Uta',
                'email'      => 'dawituta@gmail.com',
                'id_number'  => 'STF/0012/14',
                'gender'     => 'Male',
                'salutation' => 'Dr.',
                'password'   => 'dawit123',
            ],
            [
                'first_name' => 'Feven',
                'last_name'  => 'Teferi',
                'email'      => 'feventeferi@gmail.com',
                'id_number'  => 'STF/0013/14',
                'gender'     => 'Female',
                'salutation' => 'Ms.',
                'password'   => 'feven123',
            ],
        ];
        $instructors = [];
        foreach ($advisorData as $d) {
            $instructors[] = User::firstOrCreate(
                ['email' => $d['email']],
                array_merge($d, [
                    'program'   => 'Staff',
                    'role'      => 'Advisor',
                    'is_active' => true,
                ])
            );
        }

        // ══════════════════════════════════════════════════════════════
        //  UNDERGRADUATE STUDENTS
        //  1. Melkamu Sime        UGR/70043/14
        //  2. Mesay Birhanu       UGR/70044/14
        //  3. Salamlak Silesh     UGR/70045/14
        //  4. Maidote Birhan      UGR/70046/14
        //  5. Wubedl Assefa       UGR/70047/14
        // ══════════════════════════════════════════════════════════════
        $studentData = [
            [
                'first_name' => 'Melkamu',
                'last_name'  => 'Sime',
                'email'      => 'melkamusime2@gmail.com',
                'id_number'  => 'UGR/70043/14',
                'gender'     => 'Male',
                'password'   => 'melka123',
            ],
            [
                'first_name' => 'Mesay',
                'last_name'  => 'Birhanu',
                'email'      => 'mesaybirhanu@gmail.com',
                'id_number'  => 'UGR/70044/14',
                'gender'     => 'Male',
                'password'   => 'mesay123',
            ],
            [
                'first_name' => 'Salamlak',
                'last_name'  => 'Silesh Feleke',
                'email'      => 'salamlaksilesh@gmail.com',
                'id_number'  => 'UGR/70045/14',
                'gender'     => 'Female',
                'password'   => 'salam123',
            ],
            [
                'first_name' => 'Maidote',
                'last_name'  => 'Birhan',
                'email'      => 'maidotebirhan@gmail.com',
                'id_number'  => 'UGR/70046/14',
                'gender'     => 'Female',
                'password'   => 'maido123',
            ],
            [
                'first_name' => 'Wubedl',
                'last_name'  => 'Assefa',
                'email'      => 'wubedlassefa@gmail.com',
                'id_number'  => 'UGR/70047/14',
                'gender'     => 'Female',
                'password'   => 'wubed123',
            ],
        ];
        $students = [];
        foreach ($studentData as $d) {
            $students[] = User::firstOrCreate(
                ['email' => $d['email']],
                array_merge($d, [
                    'program'   => 'Undergraduate',
                    'role'      => 'Student',
                    'is_active' => true,
                ])
            );
        }

        // ══════════════════════════════════════════════════════════════
        //  GRADUATE STUDENTS
        //  1. Getamesay Mekcha    GSR/0001/14
        //  2. Tsion Zena          GSR/0002/14
        //  3. Mihret G/kirstos    GSR/0003/14
        //  4. Efrem Esayas        GSR/0004/14
        //  5. Biniyam Biru        GSR/0005/14
        // ══════════════════════════════════════════════════════════════
        $gradStudentData = [
            [
                'first_name' => 'Getamesay',
                'last_name'  => 'Mekcha',
                'email'      => 'getamesaymekcha@gmail.com',
                'id_number'  => 'GSR/0001/14',
                'gender'     => 'Male',
                'password'   => 'getam123',
            ],
            [
                'first_name' => 'Tsion',
                'last_name'  => 'Zena',
                'email'      => 'tsionzena@gmail.com',
                'id_number'  => 'GSR/0002/14',
                'gender'     => 'Female',
                'password'   => 'tsion123',
            ],
            [
                'first_name' => 'Mihret',
                'last_name'  => 'G/Kirstos',
                'email'      => 'mihretgkirstos@gmail.com',
                'id_number'  => 'GSR/0003/14',
                'gender'     => 'Female',
                'password'   => 'mihre123',
            ],
            [
                'first_name' => 'Efrem',
                'last_name'  => 'Esayas',
                'email'      => 'efreamesayas@gmail.com',
                'id_number'  => 'GSR/0004/14',
                'gender'     => 'Male',
                'password'   => 'efrem123',
            ],
            [
                'first_name' => 'Biniyam',
                'last_name'  => 'Biru',
                'email'      => 'biniyambiru@gmail.com',
                'id_number'  => 'GSR/0005/14',
                'gender'     => 'Male',
                'password'   => 'biniY123',
            ],
        ];
        foreach ($gradStudentData as $d) {
            User::firstOrCreate(
                ['email' => $d['email']],
                array_merge($d, [
                    'program'   => 'Graduate',
                    'role'      => 'GradStudent',
                    'is_active' => true,
                ])
            );
        }

        // ══════════════════════════════════════════════════════════════
        //  SAMPLE INDUSTRIAL PROJECT GROUP
        // ══════════════════════════════════════════════════════════════
        if (IndustrialProjectGroup::count() === 0 && count($students) >= 5) {
            $group = IndustrialProjectGroup::create([
                'name'             => 'OTPMS Development Team',
                'academic_year_id' => $academicYear->id,
                'created_by'       => $students[0]->id,
                'advisor_id'       => $instructors[0]->id,
                'status'           => 'advising',
            ]);

            foreach ($students as $idx => $student) {
                $group->members()->attach($student->id, [
                    'is_leader' => $idx === 0,
                    'agreed'    => true,
                ]);
            }

            // Three alternative titles (BR-03)
            $titles = [
                'Online Thesis and Project Management System for WSU',
                'Web-Based Student Academic Work Tracking System',
                'Digital Repository and Management System for Academic Works',
            ];
            foreach ($titles as $order => $title) {
                ProjectTitle::create([
                    'group_id'    => $group->id,
                    'title'       => $title,
                    'title_order' => $order + 1,
                    'status'      => $order === 0 ? 'approved' : 'rejected',
                ]);
            }
            $group->update([
                'approved_title' => 'Online Thesis and Project Management System for WSU',
            ]);
        }
    }
}
