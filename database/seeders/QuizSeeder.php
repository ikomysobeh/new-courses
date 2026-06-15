<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $leadershipCourse    = Course::where('name', 'Leadership Excellence Program')->value('id');
        $communicationCourse = Course::where('name', 'Effective Communication Workshop')->value('id');
        $salesCourse         = Course::where('name', 'Sales Mastery Bootcamp')->value('id');
        $safetyCourse        = Course::where('name', 'Workplace Safety & Compliance Training')->value('id');

        if (! $leadershipCourse) {
            $this->command?->warn('Courses not found. Run CourseSeeder first.');
            return;
        }

        $quizzes = [
            [
                'course_id'            => $leadershipCourse,
                'title'                => 'Leadership Excellence – Final Assessment',
                'description'          => 'Tests your understanding of the leadership principles covered in the programme.',
                'required_to_proceed'  => true,
                'max_attempts'         => 3,
                'retry_delay_hours'    => 24,
                'show_correct_answers' => 'after_pass',
                'time_limit_minutes'   => 30,
                'status'               => 'published',
                'total_points'         => 50,
                'pass_threshold'       => 70.00,
                'questions' => [
                    [
                        'question_text'            => 'Which leadership style focuses on setting high performance standards and modelling them personally?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Coaching', 'Pacesetting', 'Affiliative', 'Democratic'],
                        'correct_answer'           => ['Pacesetting'],
                        'correct_answer_explanation' => 'The pacesetting leader sets high standards and expects others to follow by example.',
                        'order'                    => 1,
                    ],
                    [
                        'question_text'            => 'What does emotional intelligence primarily refer to in a leadership context?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Technical expertise', 'Ability to manage one\'s emotions and understand others\'', 'Strict discipline', 'Financial acumen'],
                        'correct_answer'           => ['Ability to manage one\'s emotions and understand others\''],
                        'correct_answer_explanation' => 'Emotional intelligence is the ability to recognise, understand, and manage your own emotions and those of others.',
                        'order'                    => 2,
                    ],
                    [
                        'question_text'            => 'Which of the following are key responsibilities of an effective leader? (Select all that apply)',
                        'type'                     => 'checkbox',
                        'points'                   => 10,
                        'options'                  => ['Setting a clear vision', 'Micromanaging every task', 'Inspiring and motivating the team', 'Providing constructive feedback'],
                        'correct_answer'           => ['Setting a clear vision', 'Inspiring and motivating the team', 'Providing constructive feedback'],
                        'correct_answer_explanation' => 'Effective leaders focus on vision, motivation, and feedback — not micromanagement.',
                        'order'                    => 3,
                    ],
                    [
                        'question_text'            => 'Describe one strategy you would use to resolve a conflict between two team members.',
                        'type'                     => 'text',
                        'points'                   => 10,
                        'options'                  => null,
                        'correct_answer'           => null,
                        'correct_answer_explanation' => 'Open-ended question assessed by the facilitator.',
                        'order'                    => 4,
                    ],
                    [
                        'question_text'            => 'A transformational leader primarily aims to:',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Maintain the status quo', 'Inspire change and innovation', 'Enforce strict rules', 'Reward transactional compliance'],
                        'correct_answer'           => ['Inspire change and innovation'],
                        'correct_answer_explanation' => 'Transformational leaders inspire their teams to exceed ordinary expectations and embrace change.',
                        'order'                    => 5,
                    ],
                ],
            ],
            [
                'course_id'            => $communicationCourse,
                'title'                => 'Communication Skills – Assessment',
                'description'          => 'Evaluates your grasp of effective workplace communication techniques.',
                'required_to_proceed'  => true,
                'max_attempts'         => 3,
                'retry_delay_hours'    => 0,
                'show_correct_answers' => 'after_pass',
                'time_limit_minutes'   => 20,
                'status'               => 'published',
                'total_points'         => 40,
                'pass_threshold'       => 75.00,
                'questions' => [
                    [
                        'question_text'            => 'Active listening involves:',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Waiting for your turn to speak', 'Fully concentrating and responding thoughtfully', 'Interrupting to clarify', 'Taking notes only'],
                        'correct_answer'           => ['Fully concentrating and responding thoughtfully'],
                        'correct_answer_explanation' => 'Active listening requires full attention, understanding, and appropriate response.',
                        'order'                    => 1,
                    ],
                    [
                        'question_text'            => 'Non-verbal communication includes which of the following? (Select all that apply)',
                        'type'                     => 'checkbox',
                        'points'                   => 10,
                        'options'                  => ['Body language', 'Email tone', 'Eye contact', 'Facial expressions', 'Word choice'],
                        'correct_answer'           => ['Body language', 'Eye contact', 'Facial expressions'],
                        'correct_answer_explanation' => 'Non-verbal communication covers body language, eye contact, and facial expressions.',
                        'order'                    => 2,
                    ],
                    [
                        'question_text'            => 'What is the best approach when delivering constructive feedback?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Focus only on negatives', 'Be vague to avoid conflict', 'Be specific, objective, and solution-focused', 'Only praise the employee'],
                        'correct_answer'           => ['Be specific, objective, and solution-focused'],
                        'correct_answer_explanation' => 'Effective feedback is specific, balanced, and focused on improvement.',
                        'order'                    => 3,
                    ],
                    [
                        'question_text'            => 'Write a brief example of how you would communicate a project delay to your manager.',
                        'type'                     => 'text',
                        'points'                   => 10,
                        'options'                  => null,
                        'correct_answer'           => null,
                        'correct_answer_explanation' => 'Open-ended question assessed by the facilitator.',
                        'order'                    => 4,
                    ],
                ],
            ],
            [
                'course_id'            => $salesCourse,
                'title'                => 'Sales Mastery – Knowledge Check',
                'description'          => 'Tests understanding of the sales process and client engagement strategies.',
                'required_to_proceed'  => true,
                'max_attempts'         => 3,
                'retry_delay_hours'    => 24,
                'show_correct_answers' => 'after_max_attempts',
                'time_limit_minutes'   => 25,
                'status'               => 'published',
                'total_points'         => 40,
                'pass_threshold'       => 65.00,
                'questions' => [
                    [
                        'question_text'            => 'Which stage of the sales funnel involves nurturing prospects who have shown interest?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Prospecting', 'Qualification', 'Consideration', 'Closing'],
                        'correct_answer'           => ['Consideration'],
                        'correct_answer_explanation' => 'The consideration stage involves educating and nurturing interested prospects.',
                        'order'                    => 1,
                    ],
                    [
                        'question_text'            => 'Which techniques are effective for handling customer objections? (Select all that apply)',
                        'type'                     => 'checkbox',
                        'points'                   => 10,
                        'options'                  => ['Acknowledge the concern', 'Ignore the objection', 'Clarify with questions', 'Provide evidence or case studies', 'Pressure the customer'],
                        'correct_answer'           => ['Acknowledge the concern', 'Clarify with questions', 'Provide evidence or case studies'],
                        'correct_answer_explanation' => 'Effective objection handling involves empathy, clarification, and evidence.',
                        'order'                    => 2,
                    ],
                    [
                        'question_text'            => 'What does SPIN selling stand for?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => [
                            'Strategy, Process, Insights, Numbers',
                            'Situation, Problem, Implication, Need-Payoff',
                            'Sales, Pitch, Interest, Negotiation',
                            'Scope, Plan, Impact, Need',
                        ],
                        'correct_answer'           => ['Situation, Problem, Implication, Need-Payoff'],
                        'correct_answer_explanation' => 'SPIN Selling is a sales methodology developed by Neil Rackham.',
                        'order'                    => 3,
                    ],
                    [
                        'question_text'            => 'Describe one way to build long-term trust with a customer after closing a sale.',
                        'type'                     => 'text',
                        'points'                   => 10,
                        'options'                  => null,
                        'correct_answer'           => null,
                        'correct_answer_explanation' => 'Open-ended — facilitator assessed.',
                        'order'                    => 4,
                    ],
                ],
            ],
            [
                'course_id'            => $safetyCourse,
                'title'                => 'Safety & Compliance – Mandatory Assessment',
                'description'          => 'Mandatory assessment to confirm understanding of workplace safety and compliance regulations.',
                'required_to_proceed'  => true,
                'max_attempts'         => 5,
                'retry_delay_hours'    => 0,
                'show_correct_answers' => 'always',
                'time_limit_minutes'   => 20,
                'status'               => 'published',
                'total_points'         => 30,
                'pass_threshold'       => 80.00,
                'questions' => [
                    [
                        'question_text'            => 'What should you do first when you discover a fire in the workplace?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Try to extinguish it yourself', 'Raise the alarm immediately', 'Wait to see if it gets bigger', 'Call a colleague first'],
                        'correct_answer'           => ['Raise the alarm immediately'],
                        'correct_answer_explanation' => 'The priority is always to raise the alarm so that everyone can evacuate safely.',
                        'order'                    => 1,
                    ],
                    [
                        'question_text'            => 'Which items are required personal protective equipment (PPE) on a construction site? (Select all that apply)',
                        'type'                     => 'checkbox',
                        'points'                   => 10,
                        'options'                  => ['Hard hat', 'Sunglasses', 'High-visibility vest', 'Safety boots', 'Casual shoes'],
                        'correct_answer'           => ['Hard hat', 'High-visibility vest', 'Safety boots'],
                        'correct_answer_explanation' => 'Mandatory PPE on construction sites includes hard hat, hi-vis vest, and safety boots.',
                        'order'                    => 2,
                    ],
                    [
                        'question_text'            => 'How often should fire drills be conducted according to best practice?',
                        'type'                     => 'radio',
                        'points'                   => 10,
                        'options'                  => ['Once every 5 years', 'Once a year at minimum', 'Only when required by law', 'Never – they disrupt operations'],
                        'correct_answer'           => ['Once a year at minimum'],
                        'correct_answer_explanation' => 'Best practice and most regulations require fire drills at least once per year.',
                        'order'                    => 3,
                    ],
                ],
            ],
        ];

        foreach ($quizzes as $quizData) {
            $questions = $quizData['questions'];
            unset($quizData['questions']);

            $quiz = Quiz::withTrashed()->updateOrCreate(
                [
                    'course_id' => $quizData['course_id'],
                    'title'     => $quizData['title'],
                ],
                array_merge($quizData, ['deleted_at' => null])
            );

            foreach ($questions as $questionData) {
                // Encode JSON fields
                $questionData['options']         = isset($questionData['options'])
                    ? json_encode($questionData['options'])
                    : null;
                $questionData['correct_answer']  = isset($questionData['correct_answer'])
                    ? json_encode($questionData['correct_answer'])
                    : null;

                QuizQuestion::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'order'   => $questionData['order'],
                    ],
                    array_merge($questionData, ['quiz_id' => $quiz->id])
                );
            }
        }

        $this->command?->info('Quiz seeded: ' . count($quizzes) . ' quizzes with questions.');
    }
}
