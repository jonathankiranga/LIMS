import sys
import json
import pandas as pd
from sklearn.linear_model import LinearRegression
import numpy as np

# Read JSON data from PHP
data = json.loads(sys.stdin.read())

# Convert to DataFrame
df = pd.DataFrame(data)

# Ensure correct data types
df['avg_deviation'] = df['avg_deviation'].astype(float)
df['month'] = pd.to_datetime(df['month'])
df['month_num'] = df['month'].dt.month

# Predict calibration dates per equipment
predictions = {}
for equipment_id, group in df.groupby("equipment_id"):
    model = LinearRegression()
    model.fit(group[['month_num']], group['avg_deviation'])

    # Predict next 3 months
    future_months = np.array([group['month_num'].max() + i for i in range(1, 4)]).reshape(-1, 1)
    predicted_deviation = model.predict(future_months)

    # Determine when calibration is needed (threshold deviation > 5%)
    calibration_needed = None
    for i, deviation in enumerate(predicted_deviation):
        if deviation > 5:
            calibration_needed = (group['month'].max() + pd.DateOffset(months=i + 1)).strftime('%Y-%m')
            break

    predictions[equipment_id] = {
        "next_calibration": calibration_needed,
        "predicted_deviation": predicted_deviation.tolist()
    }

# Return JSON response
print(json.dumps(predictions))

