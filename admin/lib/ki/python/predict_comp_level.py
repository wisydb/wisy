#!C:/Users/pasca/AppData/Local/Programs/Python/Python310/python.exe

import sys
import os
import pickle
text = ''

title = sys.argv[1]
description = sys.argv[2]

if description == '':
    filename = sys.argv[3]
    f = open(filename, "r")
    lines = f.readlines()
    description = '\n'.join(lines)
    text = title + " \n\n " + description
else:
    text = title + " \n\n " + description

here = os.path.dirname(__file__)
file = open(here + "/data/comp-level_ai-model.pickle",'rb')
model = pickle.load(file)
prediction = model.predict([text]).tolist()
if prediction[0]:
    print(prediction[0])