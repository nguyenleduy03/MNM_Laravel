"""
Script để migrate data từ knowledge_base.json sang ChromaDB
Chạy 1 lần để chuyển dữ liệu cũ sang ChromaDB
"""
import json
import os
from dotenv import load_dotenv

load_dotenv()

# Setup Gemini
import google.generativeai as genai
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
genai.configure(api_key=GEMINI_API_KEY)

# Import ChromaDB
try:
    import chromadb
    print("✅ ChromaDB imported successfully")
except ImportError:
    print("❌ ChromaDB not installed. Run: pip install chromadb")
    exit(1)

def migrate():
    # Load old data
    json_file = "knowledge_base.json"
    if not os.path.exists(json_file):
        print(f"❌ File {json_file} not found")
        return
    
    with open(json_file, 'r', encoding='utf-8') as f:
        old_data = json.load(f)
    
    print(f"📂 Found {len(old_data)} documents in {json_file}")
    
    if len(old_data) == 0:
        print("⚠️  No documents to migrate")
        return
    
    # Initialize ChromaDB
    client = chromadb.PersistentClient(path="./chroma_db")
    
    # Delete old collection if exists
    try:
        client.delete_collection("knowledge_base")
        print("🗑️  Deleted old ChromaDB collection")
    except:
        pass
    
    # Create new collection
    collection = client.get_or_create_collection(
        name="knowledge_base",
        metadata={"hnsw:space": "cosine"}
    )
    
    # Migrate documents
    print("🔄 Migrating documents...")
    
    batch_size = 10
    for i in range(0, len(old_data), batch_size):
        batch = old_data[i:i+batch_size]
        
        ids = []
        documents = []
        embeddings = []
        metadatas = []
        
        for doc in batch:
            ids.append(doc['id'])
            documents.append(doc['document'])
            embeddings.append(doc['embedding'])
            metadatas.append(doc.get('metadata', {"source": "migrated"}))
        
        collection.add(
            ids=ids,
            documents=documents,
            embeddings=embeddings,
            metadatas=metadatas
        )
        
        print(f"  ✅ Migrated {min(i+batch_size, len(old_data))}/{len(old_data)} documents")
    
    print(f"\n✅ Migration complete! {collection.count()} documents in ChromaDB")
    print(f"📁 ChromaDB location: ./chroma_db")
    
    # Backup old file
    backup_file = "knowledge_base_backup.json"
    os.rename(json_file, backup_file)
    print(f"📦 Old file backed up to: {backup_file}")

if __name__ == "__main__":
    migrate()
