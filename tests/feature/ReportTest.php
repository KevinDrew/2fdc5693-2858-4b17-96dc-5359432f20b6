<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    public function testCanRunDiagnosticReport(): void
    {
        $out = shell_exec('php src/report.php student1 1');

        $expectedOutput =
        "Tony Stark recently completed Numeracy assessment on 16th December 2021 10:46:00 AM\n" .
        "He got 15 questions right out of 16. Details by strand given below:\n" .
        "\n" .
        "Measurement and Geometry: 7 out of 7\n" .
        "Number and Algebra: 5 out of 5\n" .
        "Statistics and Probability: 3 out of 4\n";

        $this->assertEquals($out, $expectedOutput);
    }

    public function testCanRunProgressReport(): void
    {
        $out = shell_exec('php src/report.php student1 2');

        $expectedOutput =
        "Tony Stark has completed Numeracy assessment 3 times in total.  Date and raw score given below:\n" .
        "\n" .
        "Date: 16th December 2019, Raw Score 6 out of 16.\n" .
        "Date: 16th December 2020, Raw Score 10 out of 16.\n" .
        "Date: 16th December 2021, Raw Score 15 out of 16.\n" .
        "\n" .
        "Tony Stark got 9 more correct in the recent completed assessment than the oldest\n";

        $this->assertEquals($out, $expectedOutput);
    }

    public function testCanRunFeedbackReport(): void
    {
        $out = shell_exec('php src/report.php student1 3');

        $expectedOutput =
        "Tony Stark recently completed Numeracy assessment on 16th December 2021 10:46:00 AM \n" .
        "He got 15 questions right out of 1. Feedback for wrong answers given below\n" .
        "\n" .
        "Question: What is the 'median' of the following group of numbers 5, 21, 7, 18, 9?\n" .
        "Your answer: A with value 7\n" .
        "Right answer: B with value 9\n" .
        "Hint: You must first arrange the numbers in ascending order. The median is the middle term, which in this case is 9\n";

        $this->assertEquals($out, $expectedOutput);
    }
}


