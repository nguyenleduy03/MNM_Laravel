<?php

namespace App\Http\Controllers;

use App\Models\FlashcardDeck;
use App\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FlashcardController extends Controller
{
    /**
     * Get all decks for user
     * GET /api/flashcards/decks
     */
    public function decks(Request $request)
    {
        $user = $request->user();

        $decks = FlashcardDeck::where('user_id', $user->id)
            ->withCount('flashcards')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($decks->map(fn($d) => $this->formatDeckResponse($d)));
    }

    /**
     * Create new deck
     * POST /api/flashcards/decks
     */
    public function createDeck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'isPublic' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $deck = FlashcardDeck::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? '#3B82F6',
            'icon' => $request->icon ?? '📚',
            'is_public' => $request->isPublic ?? false,
        ]);

        return response()->json($this->formatDeckResponse($deck), 201);
    }

    /**
     * Get deck with flashcards
     * GET /api/flashcards/decks/{id}
     */
    public function getDeck(Request $request, $id)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_public', true);
            })
            ->with('flashcards')
            ->firstOrFail();

        return response()->json($this->formatDeckWithCardsResponse($deck));
    }

    /**
     * Update deck
     * PUT /api/flashcards/decks/{id}
     */
    public function updateDeck(Request $request, $id)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'isPublic' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $deck->fill(array_filter([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon,
            'is_public' => $request->isPublic,
        ], fn($v) => $v !== null));

        $deck->save();

        return response()->json($this->formatDeckResponse($deck));
    }

    /**
     * Delete deck
     * DELETE /api/flashcards/decks/{id}
     */
    public function deleteDeck(Request $request, $id)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $deck->delete();

        return response()->json(['message' => 'Đã xóa bộ flashcard']);
    }

    /**
     * Add flashcard to deck
     * POST /api/flashcards/decks/{deckId}/cards
     */
    public function addCard(Request $request, $deckId)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $deckId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'back' => 'required|string',
            'hint' => 'nullable|string',
            'explanation' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $card = Flashcard::create([
            'deck_id' => $deckId,
            'user_id' => $user->id,
            'front' => $request->front,
            'back' => $request->back,
            'hint' => $request->hint,
            'explanation' => $request->explanation,
            'tags' => $request->tags,
            'source_type' => Flashcard::SOURCE_MANUAL,
        ]);

        return response()->json($this->formatCardResponse($card), 201);
    }

    /**
     * Update flashcard
     * PUT /api/flashcards/cards/{id}
     */
    public function updateCard(Request $request, $id)
    {
        $user = $request->user();

        $card = Flashcard::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'front' => 'nullable|string',
            'back' => 'nullable|string',
            'hint' => 'nullable|string',
            'explanation' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $card->fill(array_filter([
            'front' => $request->front,
            'back' => $request->back,
            'hint' => $request->hint,
            'explanation' => $request->explanation,
            'tags' => $request->tags,
        ], fn($v) => $v !== null));

        $card->save();

        return response()->json($this->formatCardResponse($card));
    }

    /**
     * Delete flashcard
     * DELETE /api/flashcards/cards/{id}
     */
    public function deleteCard(Request $request, $id)
    {
        $user = $request->user();

        $card = Flashcard::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $card->delete();

        return response()->json(['message' => 'Đã xóa flashcard']);
    }

    /**
     * Generate flashcards from content using AI
     * POST /api/flashcards/generate
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deckId' => 'required|integer|exists:flashcard_decks,id',
            'content' => 'required|string',
            'numCards' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $deck = FlashcardDeck::where('id', $request->deckId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $fastApiUrl = env('FASTAPI_URL', 'http://localhost:8001');

        try {
            $response = Http::timeout(60)->post("{$fastApiUrl}/api/ai/generate-flashcards", [
                'content' => $request->content,
                'num_cards' => $request->numCards ?? 10,
            ]);

            if (!$response->successful()) {
                return $this->error('Failed to generate flashcards', 500);
            }

            $aiCards = $response->json('flashcards') ?? [];
            $createdCards = [];

            foreach ($aiCards as $c) {
                $card = Flashcard::create([
                    'deck_id' => $deck->id,
                    'user_id' => $user->id,
                    'front' => $c['front'] ?? $c['question'],
                    'back' => $c['back'] ?? $c['answer'],
                    'hint' => $c['hint'] ?? null,
                    'explanation' => $c['explanation'] ?? null,
                    'source_type' => Flashcard::SOURCE_AI_GENERATED,
                ]);
                $createdCards[] = $this->formatCardResponse($card);
            }

            return response()->json([
                'message' => 'Generated ' . count($createdCards) . ' flashcards',
                'cards' => $createdCards,
            ], 201);

        } catch (\Exception $e) {
            return $this->error('Failed to connect to AI service: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get cards for study (due for review)
     * GET /api/flashcards/decks/{deckId}/study
     */
    public function getStudyCards(Request $request, $deckId)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $deckId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_public', true);
            })
            ->firstOrFail();

        // Get all cards (in real app, filter by spaced repetition algorithm)
        $cards = Flashcard::where('deck_id', $deckId)
            ->inRandomOrder()
            ->get();

        return response()->json($cards->map(fn($c) => $this->formatCardResponse($c)));
    }

    /**
     * Get due cards for review
     * GET /api/flashcards/study/due
     */
    public function getDueCards(Request $request)
    {
        $user = $request->user();
        $deckId = $request->input('deckId');
        $limit = $request->input('limit', 50);

        $query = Flashcard::where('user_id', $user->id);
        
        if ($deckId) {
            $query->where('deck_id', $deckId);
        }

        // In real app, filter by next_review_date <= now()
        $cards = $query->inRandomOrder()->take($limit)->get();

        return response()->json($cards->map(fn($c) => $this->formatCardResponse($c)));
    }

    /**
     * Get new cards
     * GET /api/flashcards/study/new
     */
    public function getNewCards(Request $request)
    {
        $user = $request->user();
        $deckId = $request->input('deckId');
        $limit = $request->input('limit', 20);

        $query = Flashcard::where('user_id', $user->id);
        
        if ($deckId) {
            $query->where('deck_id', $deckId);
        }

        // In real app, filter cards that haven't been reviewed yet
        $cards = $query->orderBy('created_at', 'desc')->take($limit)->get();

        return response()->json($cards->map(fn($c) => $this->formatCardResponse($c)));
    }

    /**
     * Submit review
     * POST /api/flashcards/study/review
     */
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flashcardId' => 'required|integer|exists:flashcards,id',
            'quality' => 'required|integer|min:0|max:5', // 0-5 rating
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();
        $card = Flashcard::where('id', $request->flashcardId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // In real app, update spaced repetition data here
        // For now, just return the card
        return response()->json($this->formatCardResponse($card));
    }

    /**
     * Get deck stats
     * GET /api/flashcards/stats/deck/{deckId}
     */
    public function getDeckStats(Request $request, $deckId)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $deckId)
            ->where('user_id', $user->id)
            ->withCount('flashcards')
            ->firstOrFail();

        return response()->json([
            'deckId' => $deck->id,
            'deckName' => $deck->name,
            'totalCards' => $deck->flashcards_count,
            'newCards' => $deck->flashcards_count, // In real app, calculate properly
            'dueCards' => 0, // In real app, calculate properly
            'masteredCards' => 0,
        ]);
    }

    /**
     * Get overview stats
     * GET /api/flashcards/stats/overview
     */
    public function getOverviewStats(Request $request)
    {
        $user = $request->user();

        $decks = FlashcardDeck::where('user_id', $user->id)
            ->withCount('flashcards')
            ->get();

        $totalCards = $decks->sum('flashcards_count');

        return response()->json([
            'totalDecks' => $decks->count(),
            'totalCards' => $totalCards,
            'newCards' => $totalCards,
            'dueCards' => 0,
            'studyStreak' => 0,
            'decks' => $decks->map(fn($d) => $this->formatDeckResponse($d)),
        ]);
    }

    /**
     * Get cards in deck
     * GET /api/flashcards/decks/{deckId}/cards
     */
    public function getCardsInDeck(Request $request, $deckId)
    {
        $user = $request->user();

        $deck = FlashcardDeck::where('id', $deckId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_public', true);
            })
            ->firstOrFail();

        $cards = Flashcard::where('deck_id', $deckId)->get();

        return response()->json($cards->map(fn($c) => $this->formatCardResponse($c)));
    }

    /**
     * Get single card
     * GET /api/flashcards/cards/{id}
     */
    public function getCard(Request $request, $id)
    {
        $user = $request->user();

        $card = Flashcard::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json($this->formatCardResponse($card));
    }

    private function formatDeckResponse(FlashcardDeck $deck): array
    {
        return [
            'id' => $deck->id,
            'name' => $deck->name,
            'description' => $deck->description,
            'color' => $deck->color,
            'icon' => $deck->icon,
            'isPublic' => $deck->is_public,
            'cardCount' => $deck->flashcards_count ?? $deck->flashcards()->count(),
            'createdAt' => $deck->created_at?->toISOString(),
            'updatedAt' => $deck->updated_at?->toISOString(),
        ];
    }

    private function formatDeckWithCardsResponse(FlashcardDeck $deck): array
    {
        $response = $this->formatDeckResponse($deck);
        $response['cards'] = $deck->flashcards->map(fn($c) => $this->formatCardResponse($c));
        return $response;
    }

    private function formatCardResponse(Flashcard $card): array
    {
        return [
            'id' => $card->id,
            'deckId' => $card->deck_id,
            'front' => $card->front,
            'back' => $card->back,
            'hint' => $card->hint,
            'explanation' => $card->explanation,
            'frontImageUrl' => $card->front_image_url,
            'backImageUrl' => $card->back_image_url,
            'audioUrl' => $card->audio_url,
            'tags' => $card->tags,
            'sourceType' => $card->source_type,
            'createdAt' => $card->created_at?->toISOString(),
        ];
    }
}
