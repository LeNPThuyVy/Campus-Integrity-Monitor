import sys
import os
import argparse
from sqlalchemy import create_engine, text
from dotenv import load_dotenv

# Add project root to sys.path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from api.auth import get_password_hash

def main():
    parser = argparse.ArgumentParser(description="Create an admin user for the system")
    parser.add_argument("username", help="Admin username")
    parser.add_argument("password", help="Admin password")
    args = parser.parse_args()

    load_dotenv()
    db_url = os.getenv("CIM_DATABASE_URL")
    if not db_url:
        print("Error: CIM_DATABASE_URL not set in environment.")
        return

    # Disable prepared statements for Supabase pooler
    engine = create_engine(db_url, connect_args={"options": "-c statement_timeout=30000"})
    
    hashed_password = get_password_hash(args.password)
    
    with engine.begin() as conn:
        try:
            conn.execute(
                text("INSERT INTO admin_users (username, password_hash) VALUES (:u, :p) ON CONFLICT (username) DO UPDATE SET password_hash = :p"),
                {"u": args.username, "p": hashed_password}
            )
            print(f"Successfully created/updated admin user: {args.username}")
        except Exception as e:
            print(f"Database error: {e}")

if __name__ == "__main__":
    main()
