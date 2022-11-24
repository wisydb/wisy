#!C:/Users/pasca/AppData/Local/Programs/Python/Python310/python.exe

# Setup and imports
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.multiclass import OneVsRestClassifier
from sklearn.pipeline import make_pipeline
import nltk
from nltk.corpus import stopwords
nltk.download('stopwords')
from sklearn.metrics import classification_report
from datetime import datetime
import time
import pickle
import os
import json

log = "Training results:"
log += "\nDate: " + datetime.now().strftime("%d/%m/%Y %H:%M:%S")
# Start time.
st = time.time()
# Load data.
here = os.path.dirname(__file__)
labeled_data = pd.read_json(here + '/data/labeledCourses.json', orient='records')

log += "\nTraining data size: " + str(labeled_data.shape[0])


# Set input data.
X = labeled_data.text

# Set traget data.
Y = labeled_data.label


# Split data into test and training data.
X_train, X_test, y_train, y_test = train_test_split(X, Y, test_size=1/2)


# Create a model based on Multinominal Naive Bayes.
model = make_pipeline(
    TfidfVectorizer(max_df=0.25, ngram_range=(1, 2), stop_words=stopwords.words('german')),
    OneVsRestClassifier(MultinomialNB(fit_prior=True, class_prior=None, alpha=0.001))
)

# Train the model with the train data.
model.fit(X_train, y_train)

# Create labels for the test data.
prediction = model.predict(X_test)
labels = ["A", "B", "C", "D"]
log += "\n\n" + classification_report(y_test, prediction, target_names=labels, zero_division=0)
report = classification_report(y_test, prediction, target_names=labels, zero_division=0, output_dict=True)
josn_report = json.dumps(report)

pickle.dump(model, open(here + "/data/comp-level_ai-model.pickle", 'wb'))

print(josn_report)

# End time.
et = time.time()

# Get Elapsed time.
elapsed_time = et - st
log += "\n\nExecution time: " + str(elapsed_time) + ' seconds'

try:
    logFile = open(here + "/data/trainModelLog.txt", "a")
except FileNotFoundError:
    print("The file at " + here + "/data/trainModelLog.txt" + " does not exist.")

logFile.write(log + "\n\n\n")
logFile.close()


