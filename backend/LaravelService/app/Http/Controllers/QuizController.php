<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Get quizzes by course or lesson
     * GET /api/quizzes?courseId=1 or GET /api/quizzes?lessonId=1
     * GET /api/quiz/lesson/{lessonId}
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Quiz::with('creator:id,username,full_name')
            ->withCount('questions');

        if ($request->has('courseId')) {
            $query->where('course_id', $request->courseId);
        }
        if ($request->has('lessonId')) {
            $query->where('lesson_id', $request->lessonId);
        }

        // Filter: public quizzes or own quizzes
        $query->where(function ($q) use ($user) {
            $q->where('is_public', true)
              ->orWhere('created_by', $user->id);
        });

        $quizzes = $query->orderBy('created_at', 'desc')->get();

        return response()->json($quizzes->map(fn($quiz) => $this->formatQuizListResponse($quiz)));
    }

    /**
     * Get quizzes by lesson ID
     * GET /api/quiz/lesson/{lessonId}
     */
    public function byLesson(Request $request, $lessonId)
    {
        $user = $request->user();
        
        $quizzes = Quiz::where('lesson_id', $lessonId)
            ->where(function ($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('created_by', $user->id);
            })
            ->with('creator:id,username,full_name')
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($quizzes->map(fn($quiz) => $this->formatQuizListResponse($quiz)));
    }

    /**
     * Get quiz with questions
     * GET /api/quizzes/{id}
     */
    public function show(Request $request, $id)
    {
        $quiz = Quiz::with(['questions', 'creator:id,username,full_name'])
            ->findOrFail($id);

        return response()->json($this->formatQuizResponse($quiz));
    }

    /**
     * Create quiz manually
     * POST /api/quizzes
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'courseId' => 'nullable|integer|exists:courses,id',
            'lessonId' => 'nullable|integer|exists:lessons,id',
            'difficulty' => 'nullable|string|in:EASY,MEDIUM,HARD',
            'isPublic' => 'nullable|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.optionA' => 'required|string',
            'questions.*.optionB' => 'required|string',
            'questions.*.optionC' => 'required|string',
            'questions.*.optionD' => 'required|string',
            'questions.*.correctAnswer' => 'required|string|in:A,B,C,D',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'course_id' => $request->courseId,
            'lesson_id' => $request->lessonId,
            'created_by' => $user->id,
            'difficulty' => $request->difficulty ?? 'MEDIUM',
            'is_public' => $request->isPublic ?? false,
        ]);

        // Create questions
        foreach ($request->questions as $q) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $q['question'],
                'option_a' => $q['optionA'],
                'option_b' => $q['optionB'],
                'option_c' => $q['optionC'],
                'option_d' => $q['optionD'],
                'correct_answer' => $q['correctAnswer'],
                'explanation' => $q['explanation'] ?? null,
            ]);
        }

        $quiz->load('questions');

        return response()->json($this->formatQuizResponse($quiz), 201);
    }

    /**
     * Generate quiz using AI
     * POST /api/quizzes/generate
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lessonId' => 'required|integer|exists:lessons,id',
            'numQuestions' => 'nullable|integer|min:1|max:20',
            'difficulty' => 'nullable|string|in:EASY,MEDIUM,HARD',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $lesson = Lesson::findOrFail($request->lessonId);
        $user = $request->user();

        // Call FastAPI AI service to generate quiz
        $fastApiUrl = env('FASTAPI_URL', 'http://localhost:8001');
        
        try {
            $response = Http::timeout(60)->post("{$fastApiUrl}/api/ai/generate-quiz", [
                'content' => $lesson->content,
                'num_questions' => $request->numQuestions ?? 10,
                'difficulty' => strtolower($request->difficulty ?? 'medium'),
            ]);

            if (!$response->successful()) {
                return $this->error('Failed to generate quiz from AI service', 500);
            }

            $aiQuestions = $response->json('questions');

            // Create quiz
            $quiz = Quiz::create([
                'title' => "Quiz: {$lesson->title}",
                'description' => "Auto-generated quiz for lesson: {$lesson->title}",
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
                'created_by' => $user->id,
                'difficulty' => strtoupper($request->difficulty ?? 'MEDIUM'),
                'is_public' => false,
            ]);

            // Create questions from AI response
            foreach ($aiQuestions as $q) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $q['question'],
                    'option_a' => $q['a'],
                    'option_b' => $q['b'],
                    'option_c' => $q['c'],
                    'option_d' => $q['d'],
                    'correct_answer' => $q['correct'],
                    'explanation' => $q['explanation'] ?? null,
                ]);
            }

            $quiz->load('questions');

            return response()->json($this->formatQuizResponse($quiz), 201);

        } catch (\Exception $e) {
            return $this->error('Failed to connect to AI service: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit quiz answers
     * POST /api/quizzes/{id}/submit
     */
    public function submit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.questionId' => 'required|integer',
            'answers.*.answer' => 'required|string|in:A,B,C,D',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $quiz = Quiz::with('questions')->findOrFail($id);
        $user = $request->user();

        $totalQuestions = $quiz->questions->count();
        $correctAnswers = 0;
        $answerDetails = [];

        foreach ($request->answers as $answer) {
            $question = $quiz->questions->firstWhere('id', $answer['questionId']);
            if ($question) {
                $isCorrect = $question->correct_answer === $answer['answer'];
                if ($isCorrect) {
                    $correctAnswers++;
                }
                $answerDetails[] = [
                    'questionId' => $question->id,
                    'userAnswer' => $answer['answer'],
                    'correctAnswer' => $question->correct_answer,
                    'isCorrect' => $isCorrect,
                ];
            }
        }

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Save result
        $result = QuizResult::create([
            'quiz_id' => $id,
            'user_id' => $user->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'answers_json' => $answerDetails,
        ]);

        return response()->json([
            'id' => $result->id,
            'quizId' => $id,
            'score' => $score,
            'totalQuestions' => $totalQuestions,
            'correctAnswers' => $correctAnswers,
            'answers' => $answerDetails,
            'createdAt' => $result->created_at->toISOString(),
        ]);
    }

    /**
     * Get quiz results for user
     * GET /api/quizzes/{id}/results
     */
    public function results(Request $request, $id)
    {
        $user = $request->user();

        $results = QuizResult::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($results->map(fn($r) => [
            'id' => $r->id,
            'score' => $r->score,
            'totalQuestions' => $r->total_questions,
            'correctAnswers' => $r->correct_answers,
            'createdAt' => $r->created_at->toISOString(),
        ]));
    }

    /**
     * Delete quiz
     * DELETE /api/quizzes/{id}
     */
    public function destroy(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);
        $user = $request->user();

        if ($quiz->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to delete this quiz', 403);
        }

        $quiz->delete();

        return response()->json(['message' => 'Xóa quiz thành công']);
    }

    private function formatQuizListResponse(Quiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'courseId' => $quiz->course_id,
            'lessonId' => $quiz->lesson_id,
            'difficulty' => $quiz->difficulty,
            'questionCount' => $quiz->questions_count ?? $quiz->questions()->count(),
            'isPublic' => $quiz->is_public,
            'creatorName' => $quiz->creator?->full_name ?? $quiz->creator?->username,
            'createdAt' => $quiz->created_at?->toISOString(),
        ];
    }

    private function formatQuizResponse(Quiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'courseId' => $quiz->course_id,
            'lessonId' => $quiz->lesson_id,
            'difficulty' => $quiz->difficulty,
            'isPublic' => $quiz->is_public,
            'createdBy' => $quiz->created_by,
            'creatorName' => $quiz->creator?->full_name ?? $quiz->creator?->username,
            'questions' => $quiz->questions->map(fn($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'optionA' => $q->option_a,
                'optionB' => $q->option_b,
                'optionC' => $q->option_c,
                'optionD' => $q->option_d,
                'correctAnswer' => $q->correct_answer,
                'explanation' => $q->explanation,
            ]),
            'createdAt' => $quiz->created_at?->toISOString(),
        ];
    }
}
