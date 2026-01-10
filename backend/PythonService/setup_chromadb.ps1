# Setup ChromaDB for RAG
Write-Host "🔧 Setting up ChromaDB for RAG..." -ForegroundColor Cyan

# Install ChromaDB
Write-Host "📦 Installing ChromaDB..." -ForegroundColor Yellow
pip install chromadb

# Check if installation successful
$chromaInstalled = pip show chromadb 2>$null
if ($chromaInstalled) {
    Write-Host "✅ ChromaDB installed successfully!" -ForegroundColor Green
} else {
    Write-Host "❌ Failed to install ChromaDB" -ForegroundColor Red
    exit 1
}

# Migrate data
Write-Host "`n🔄 Migrating data from JSON to ChromaDB..." -ForegroundColor Yellow
python migrate_to_chromadb.py

Write-Host "`n✅ Setup complete!" -ForegroundColor Green
Write-Host "📁 ChromaDB data stored in: ./chroma_db" -ForegroundColor Cyan
Write-Host "🚀 Restart Python service to use ChromaDB" -ForegroundColor Cyan
