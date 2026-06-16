<?php



namespace App\Services\Quiz;



use App\Models\Quiz;

use App\Models\QuizQuestion;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



class QuizService

{

    public function __construct(

        private readonly QuizQuestionService $questionService

    ) {}



    public function getAllForAdmin(array $filters = []): Collection

    {

        $query = Quiz::query()->with(['course'])->withCount('questions');



        if (!empty($filters['status'])) {

            $query->where('status', $filters['status']);

        }



        if (!empty($filters['course_id'])) {

            $query->where('course_id', $filters['course_id']);

        }



        return $query->orderByDesc('id')->get();

    }



    public function getAllForUser(int $userId): Collection

    {

        return Quiz::query()

            ->where('status', 'published')

            ->whereHas('assignments', fn ($q) => $q->where('user_id', $userId))

            ->withExists(['attempts as user_passed' => fn ($q) => $q->where('user_id', $userId)->where('passed', true)])

            ->withMax(['attempts as user_total_score' => fn ($q) => $q->where('user_id', $userId)->whereNotNull('completed_at')], 'total_score')

            ->get();

    }



    public function getById(int $id): Quiz

    {

        return Quiz::query()

            ->with(['questions' => fn ($q) => $q->orderBy('order')])

            ->with('module')

            ->findOrFail($id);

    }



    public function createQuiz(array $data): Quiz

    {

        return DB::transaction(function () use ($data) {

            $questions = $data['questions'] ?? [];

            unset($data['questions']);



            $quiz = Quiz::query()->create([

                'course_id'            => $data['course_id'] ?? null,

                'course_online_id'     => $data['course_online_id']
                    ?? optional(\App\Models\CourseModule::find($data['module_id'] ?? null))->course_online_id,

                'module_id'            => $data['module_id'] ?? null,

                'title'                => $data['title'],

                'description'          => $data['description'] ?? null,

                'required_to_proceed'  => $data['required_to_proceed'] ?? true,

                'max_attempts'         => $data['max_attempts'] ?? 3,

                'retry_delay_hours'    => $data['retry_delay_hours'] ?? 0,

                'show_correct_answers' => $data['show_correct_answers'] ?? 'after_pass',

                'deadline'             => $data['deadline'] ?? null,

                'time_limit_minutes'   => $data['time_limit_minutes'] ?? null,

                'status'               => $data['status'] ?? 'draft',

                'pass_threshold'       => $data['pass_threshold'] ?? 80.00,

            ]);



            foreach ($questions as $questionData) {

                $this->questionService->addQuestion($quiz->id, $questionData, recompute: false);

            }



            if (count($questions) > 0) {

                $this->questionService->recalculateTotalPoints($quiz->id);

            }



            return $quiz->fresh(['questions']);

        });

    }



    public function updateQuiz(Quiz $quiz, array $data): Quiz

    {

        return DB::transaction(function () use ($quiz, $data) {

            if (isset($data['status']) && $data['status'] === 'published') {

                if ($quiz->questions()->count() === 0) {

                    throw ValidationException::withMessages([

                        'status' => ['A quiz with no questions cannot be published.'],

                    ]);

                }

            }



            $payload = [];



            $scalar = ['title', 'required_to_proceed', 'max_attempts', 'retry_delay_hours',

                       'show_correct_answers', 'status', 'pass_threshold'];

            foreach ($scalar as $field) {

                if (array_key_exists($field, $data)) {

                    $payload[$field] = $data[$field];

                }

            }



            $nullable = ['description', 'deadline', 'time_limit_minutes'];

            foreach ($nullable as $field) {

                if (array_key_exists($field, $data)) {

                    $payload[$field] = $data[$field];

                }

            }



            if (!empty($payload)) {

                $quiz->update($payload);

            }



            return $quiz->fresh();

        });

    }



    public function deleteQuiz(Quiz $quiz): void

    {

        $hasCompletedAttempts = $quiz->attempts()

            ->whereNotNull('completed_at')

            ->exists();



        if ($hasCompletedAttempts) {

            throw ValidationException::withMessages([

                'quiz_id' => ['Cannot delete a quiz that has completed attempts.'],

            ]);

        }



        $quiz->delete();

    }



    public function getAdminQuizCards(): array

    {

        $total     = Quiz::query()->count();

        $draft     = Quiz::query()->where('status', 'draft')->count();

        $published = Quiz::query()->where('status', 'published')->count();

        $archived  = Quiz::query()->where('status', 'archived')->count();



        return [

            ['key' => 'total_quizzes',     'title' => 'Total Quizzes',     'value' => $total],

            ['key' => 'draft_quizzes',     'title' => 'Draft Quizzes',     'value' => $draft],

            ['key' => 'published_quizzes', 'title' => 'Published Quizzes', 'value' => $published],

            ['key' => 'archived_quizzes',  'title' => 'Archived Quizzes',  'value' => $archived],

        ];

    }

}

