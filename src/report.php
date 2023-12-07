<?php
define('REPORT_TYPE_DIAGNOSTIC', 1);
define('REPORT_TYPE_PROGRESS', 2);
define('REPORT_TYPE_FEEDBACK', 3);

///////////////////////////////////////////////////////////////////////////////////////////// RECEIVE USER INPUT
if ($argc > 1) {
    $studentId = $argv[1];   // command line arguments input convenient for testing
    $reportType = $argv[2];
} else {
    echo "Please enter the following\n";
    echo "Student ID: ";
    $studentId = readline();
    $student = null;

    echo "Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback): <report-number-by-user> ";
    $reportType = readline();
}

///////////////////////////////////////////////////////////////////////////////////////////// READ IMPORT FILES
try {
    $assessments        = json_decode(file_get_contents('data/assessments.json'));
    $questions          = json_decode(file_get_contents('data/questions.json'));
    $studentResponses   = json_decode(file_get_contents('data/student-responses.json'));
    $students           = json_decode(file_get_contents('data/students.json'));
} catch (Exception $e) {
    echo "Error processing input files\n";
    echo $e->getMessage() ."\n";
}

///////////////////////////////////////////////////////////////////////////////////////////// MANUIPULATE DATA INTO A CONVENIENT FORM
$assessmentsIndexedById = indexByKey($assessments, 'id');

// create a structured array of questions indexed by the questionId
$questionsIndexedById = [];
$strandTotals = [];  // indexed by [AssessmentName][strand]  - needed for [out of] value in progress report
foreach ($questions as $question) {
    $questionsIndexedById[$question->id] = $question;
    $strandTotals[$question->strand] = $strandTotals[$question->strand] ?? 0; // initialise to zero if not set for this index
    $strandTotals[$question->strand]++;
}

// get the student from the loaded students data - so we have their name
foreach ($students as $studentElement) {
    if ($studentElement->id === $studentId) {
        $student = $studentElement;
    }
}

if (!isset($student)) {
    echo ("Error. Student not found with Student ID: '$studentId'\n");
}

// get all responses by this student - and create a structured array $thisStudentScores[assessmentId][date]
$thisStudentScores = [];
// store responses for feedback, to  be indexed by assessment name, date, questionId
$assResIxByAssNameThenDate = [];
foreach ($studentResponses as $studentResponse) {
    if ($studentResponse->student->id === $studentId && isset($studentResponse->completed)) {
        $assessmentName = $assessmentsIndexedById[$studentResponse->assessmentId]->name;

        $timeStamp = strtotime(str_replace('/', '-', $studentResponse->completed));

        $strandResults = [];
        foreach ($studentResponse->responses as $response) {
            $theirAnswer = $response->response;
            $correctAnswer = $questionsIndexedById[$response->questionId]->config->key;

            // store this for indexing the array
            $strand = $questionsIndexedById[$response->questionId]->strand;

            $strandResults[$strand] = $strandResults[$strand] ?? 0; // initialise to zero if not set for this index
            if ($theirAnswer == $correctAnswer) {
                $strandResults[$strand]++;
            } else { // only need to store the correction for wrong answer
                $assResIxByAssNameThenDate[$assessmentName][$timeStamp][$response->questionId] = $response;
            }
        }

        ksort($strandResults); // keysort by strand names for convenient display ofr results after

        $thisStudentScores[$assessmentName][$timeStamp] = $strandResults;
    }
}

//////////////////////////////////////////////////////////////////////////////////////////// RUN CHOSEN REPORT WITH MANIPULATED DATA
switch($reportType) {
    case REPORT_TYPE_DIAGNOSTIC:
        diagnosticReport($student, $thisStudentScores, $strandTotals);
        break;
    case REPORT_TYPE_PROGRESS:
        progressReport($student, $thisStudentScores, $strandTotals);
        break;
    case REPORT_TYPE_FEEDBACK:
        feedbackReport($student, $thisStudentScores, $questionsIndexedById, $assResIxByAssNameThenDate);
        break;
    default:
        echo "Invalid report type: '$reportType'";
}

/**
 * Receives an array with no index and indexes it on the name stored in $key
 *
 * @param mixed $rawArray  array with numeric index where we want to get it indexed with value stored in $key
 * @param mixed $key text value of the key we want to use as the index for te array
 * @return array new array - now indexed
 */
function indexByKey($rawArray, $key) : array
{
    $indexedArray = [];
    foreach ($rawArray as $el) {
        $indexedArray[$el->$key] = $el;
    }
    return $indexedArray;
}

/**
 * Report from option #1 Diagnostic Report
 *
 * @param mixed $student object for getting their name
 * @param mixed $thisStudentScores  what the selected student scored for each attempt and each strand
 * @param mixed $strandTotals total possible scores for each strand
 * @return void
 */
function diagnosticReport($student, $thisStudentScores, $strandTotals) {
    // the sample data only contains one assessment, but I did not assume this, so I loop through them

    foreach ($thisStudentScores as $assessment => $thisStudentScoresAssessment) {
        $lastDate = array_keys($thisStudentScoresAssessment)[count($thisStudentScoresAssessment) - 1];
        $result = $thisStudentScoresAssessment[$lastDate];

        echo "{$student->firstName} {$student->lastName} recently completed $assessment assessment on ";
        echo date("jS F Y H:i:s A\n", $lastDate);  // e.g. 16th December 2021 10:46 AM
        echo "He got ". array_sum($result) ." questions right out of ". array_sum($strandTotals) .". Details by strand given below:\n\n";

        foreach ($result as $strandName => $strandScore) {
            echo "$strandName: $strandScore out of {$strandTotals[$strandName]}\n";
        }
    }
}

/**
 * Report from option #2 Progress Report
 *
 * @param mixed $student object for getting their name
 * @param mixed $thisStudentScores  what the selected student scored for each attempt and each strand
 * @param mixed $strandTotals  total possible scores for each strand
 * @return void
 */
function progressReport($student, $thisStudentScores, $strandTotals) {
    foreach ($thisStudentScores as $assessment => $thisStudentScoresAssessment) {
        echo "{$student->firstName} {$student->lastName} has completed $assessment assessment ". count($thisStudentScoresAssessment);
        echo " times in total.  Date and raw score given below:\n\n";

        $firstScore = -1;
        foreach ($thisStudentScoresAssessment as $date => $result) {
            echo "Date: ";
            echo date('jS F Y, ', $date);
            echo "Raw Score ". array_sum($result) ." out of ". array_sum($strandTotals) .".\n";

            if ($firstScore == -1) {
                $firstScore = array_sum($result);
            }

            $lastScore = array_sum($result);
        }
    }

    $diff = $lastScore - $firstScore;
    echo "\n{$student->firstName} {$student->lastName} got ";
    echo abs($diff) . ' ' . ($diff > 0 ? 'more' : 'less');   // check to display 'more' or 'less' (in case the score went down)
    echo " correct in the recent completed assessment than the oldest\n";
}

/**
 * Report from option #3 Feedback Report
 *
 * @param mixed $student object for getting their name
 * @param mixed $thisStudentScores   what the selected student scored for each attempt and each strand
 * @param mixed $questionsIndexedById  array of questions indexed from questions.json
 * @param mixed $assResIxByAssNameThenDate  student responses indexed by assessment name, date, questionId
 * @return void
 */
function feedbackReport($student, $thisStudentScores, $questionsIndexedById, $assResIxByAssNameThenDate) {
    foreach ($assResIxByAssNameThenDate as $assessment => $timestampResponses) {
        // $timestampResponses is indexed by timestamp

        $lastDate = array_keys($timestampResponses)[count($timestampResponses) - 1]; // find the array key of the last assessment
        $questions = $timestampResponses[$lastDate];

        echo "{$student->firstName} {$student->lastName} recently completed $assessment assessment on ";
        echo date("jS F Y H:i:s A \n", $lastDate);
        echo "He got ". array_sum($thisStudentScores[$assessment][$lastDate]) ." questions right out of ". count($questions) .". Feedback for wrong answers given below\n\n";

        foreach ($questions as $questionId => $question) {  // array only contains wrong responses
            echo "Question: {$questionsIndexedById[$questionId]->stem}\n";
            echo "Your answer: ";
            foreach ($questionsIndexedById[$questionId]->config->options as $option) {
                if ($option->id === $question->response) {  // find the correct response
                    echo "{$option->label} with value {$option->value}\n";
                }
                if ($option->id === $questionsIndexedById[$questionId]->config->key) {
                    $rightLabel = "{$option->label} with value {$option->value}"; // store the output string to display after
                }
            }
            echo "Right answer: $rightLabel\n";
            echo "Hint: {$questionsIndexedById[$questionId]->config->hint}\n";
        }
    }
}


