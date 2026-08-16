<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Student Self-Registration Provisioning
        |--------------------------------------------------------------------------
        |
        | Hanya auth user yang berasal dari form registrasi siswa yang akan
        | dibuatkan profile + wallet.
        |
        | Role dan saldo tidak pernah dipercaya dari metadata frontend.
        |
        */

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.handle_new_student_user()
            RETURNS trigger
            LANGUAGE plpgsql
            SECURITY DEFINER
            SET search_path = ''
            AS $$
            DECLARE
                registration_source text;
                student_name text;
            BEGIN
                registration_source :=
                    COALESCE(
                        NEW.raw_user_meta_data
                            ->> 'registration_source',
                        ''
                    );

                /*
                |--------------------------------------------------------------------------
                | Ignore Non-Student Self Registration
                |--------------------------------------------------------------------------
                */

                IF registration_source
                    <> 'student_self_registration'
                THEN
                    RETURN NEW;
                END IF;

                student_name :=
                    BTRIM(
                        COALESCE(
                            NEW.raw_user_meta_data
                                ->> 'full_name',
                            ''
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Required Student Name
                |--------------------------------------------------------------------------
                */

                IF CHAR_LENGTH(student_name) < 3 THEN
                    RAISE EXCEPTION
                        'full_name is required for student registration';
                END IF;

                /*
                |--------------------------------------------------------------------------
                | Profile
                |--------------------------------------------------------------------------
                */

                INSERT INTO public.profiles (
                    id,
                    name,
                    phone,
                    avatar_url,
                    role,
                    created_at,
                    updated_at
                )
                VALUES (
                    NEW.id,
                    student_name,
                    NULL,
                    NULL,
                    'student',
                    NOW(),
                    NOW()
                )
                ON CONFLICT (id) DO NOTHING;

                /*
                |--------------------------------------------------------------------------
                | Student Wallet
                |--------------------------------------------------------------------------
                */

                INSERT INTO public.wallets (
                    user_id,
                    balance,
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    NEW.id,
                    0,
                    TRUE,
                    NOW(),
                    NOW()
                )
                ON CONFLICT (user_id) DO NOTHING;

                RETURN NEW;
            END;
            $$;
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Auth User Trigger
        |--------------------------------------------------------------------------
        */

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS
                on_auth_user_created_schoolcanteen
            ON auth.users;

            CREATE TRIGGER
                on_auth_user_created_schoolcanteen
            AFTER INSERT ON auth.users
            FOR EACH ROW
            EXECUTE FUNCTION
                public.handle_new_student_user();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS
                on_auth_user_created_schoolcanteen
            ON auth.users;

            DROP FUNCTION IF EXISTS
                public.handle_new_student_user();
        SQL);
    }
};
