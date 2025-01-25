# Import required libraries
from flask import Flask, request, jsonify
from flask_cors import CORS  # For enabling CORS
import numpy as np
import joblib
import os
import mysql.connector  # MySQL library
from sklearn.linear_model import LogisticRegression
import re  # For regex processing

# ---------------------------
# MODEL SETUP AND FILE CHECK
# ---------------------------
MODEL_FILE = "model.joblib"

if not os.path.exists(MODEL_FILE):
    # Create and save a dummy model with 4 features
    dummy_model = LogisticRegression()
    X_dummy = [
        [1, 0.0, 0.0, 3000],  # Severity, Lat, Lng, Impact
        [2, 40.7128, -74.006, 500],
        [3, 15.3153, 117.6018, 200],
        [4, -3.4653, -62.2159, 1000],
        [1, 11.3456, 92.3343, 150],
    ]
    y_dummy = [0, 1, 1, 0, 0]  # Example labels (Low Impact = 0, High Impact = 1)

    dummy_model.fit(X_dummy, y_dummy)
    joblib.dump(dummy_model, MODEL_FILE)
    print(f"Model with 4 features created and saved as {MODEL_FILE}")

model = joblib.load(MODEL_FILE)

# ---------------------------
# DATABASE CONNECTION
# ---------------------------
DB_CONFIG = {
    "host": "localhost",
    "user": "root",       # Change this to your DB username
    "password": "",       # Change this to your DB password
    "database": "emrs_db",  # Your project database
}

def fetch_report_data():
    """
    Fetch relevant data from the reports table and clean impact values.
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        query = "SELECT severity, lat, lng, impact, location FROM reports LIMIT 100"
        cursor.execute(query)
        reports = cursor.fetchall()

        # Process and clean the data
        severity_map = {"Low": 1, "Medium": 2, "High": 3, "Critical": 4}
        for report in reports:
            # Map severity levels to numeric values
            report['severity'] = severity_map.get(report['severity'], 0)
            
            # Extract numeric value from 'impact'
            impact_match = re.search(r'(\d+)', str(report['impact']))  # Look for the first number
            report['impact'] = int(impact_match.group(1)) if impact_match else 0

            # Set lat/lng to 0 if null
            report['lat'] = float(report['lat']) if report['lat'] else 0.0
            report['lng'] = float(report['lng']) if report['lng'] else 0.0

        cursor.close()
        conn.close()
        return reports
    except Exception as e:
        print("Database error:", e)
        return []

# ---------------------------
# FLASK APP SETUP
# ---------------------------
app = Flask(__name__)
CORS(app)  # Enable Cross-Origin Resource Sharing (CORS)

@app.route("/")
def home():
    return jsonify({"message": "AI Service for EMRS is running!"})

@app.route("/predict", methods=["POST"])
def predict():
    """
    API endpoint for AI predictions using input JSON.
    """
    try:
        # Debugging: Log the incoming request JSON
        data = request.json
        print("Received input data:", data)

        # Check for valid JSON input
        if not data or "features" not in data:
            print("Invalid input: Missing 'features' key")
            return jsonify({"error": "Invalid input data. Provide 'features' key."}), 400

        # Extract features and validate structure
        features = np.array(data["features"]).reshape(1, -1)
        print("Processed input features for prediction:", features)

        # Perform prediction
        prediction = model.predict(features)
        print("Generated prediction:", prediction)

        return jsonify({"prediction": prediction.tolist()})
    except Exception as e:
        print("Error in prediction:", str(e))
        return jsonify({"error": str(e)}), 500

@app.route("/predict_from_db", methods=["GET"])
def predict_from_db():
    """
    API endpoint for AI predictions using database reports.
    """
    try:
        # Fetch data from database
        reports = fetch_report_data()
        print("Fetched reports from database:", reports)
        
        if not reports:
            print("No data found in the database")
            return jsonify({"error": "No data found in the database."}), 404
        
        # Process data into features for prediction
        features = []
        for report in reports:
            severity = report['severity']
            lat = report['lat']
            lng = report['lng']
            impact = report['impact']
            features.append([severity, lat, lng, impact])

        print("Processed features for prediction:", features)

        # Convert to numpy array and predict
        features = np.array(features)
        predictions = model.predict(features)
        print("Generated predictions:", predictions)

        # Map numeric predictions to messages
        prediction_messages = ["Low Impact", "Moderate Impact", "High Impact", "Severe Impact"]
        response = []
        for i, report in enumerate(reports):
            response.append({
                "report": {
                    "location": f"Location {i + 1}",
                    "address": report.get('location', 'Not Provided'),
                    "impact": report['impact']
                },
                "prediction": prediction_messages[predictions[i] % len(prediction_messages)]
            })

        return jsonify(response)
    except Exception as e:
        print("Error in prediction from DB:", str(e))
        return jsonify({"error": str(e)}), 500

# ---------------------------
# RUN FLASK SERVER
# ---------------------------
if __name__ == "__main__":
    print("Starting AI Service...")
    app.run(debug=True, port=8000)
