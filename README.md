# ACER coding challenge attempt by Kevin Drew

## Development Environment - Technical Details
I have chosen PHP for this challenge.
My solution is grouped into these stages
1. Receive user input - (with error checking)
2. Read import files -  (with error checking)
3. Manipulate data into a convenient form
4. Run chosen report with manipulated data


# Assumptions
## Simplicity
This program has a fairly limited scope.  In most projects I would approach them creating a website with a polished front end and Laravel back end.  I would create data models as part of an MVC solution.  A CLI was requested with 3 use cases, so I kept my solution to one PHP file because this seemed appropriate. Although a full blown MVC with front end / back end would be possible with upload features, data storage, authenticated users and user access management could be developed.

## Injection Security
Data source is JSON. JSON Data has security vulnerabilities in some cases.  If the data could be uploaded, it could contain JSON injection attacks which could attempt to elevate privileges or erase data.  It is not necessary in the scope of this project to protect data. It is not possible to write data from this program, and no user database is involved, or any user access level system.

# Instructions
## Checkout
``` 
git clone https://github.com/KevinDrew/2fdc5693-2858-4b17-96dc-5359432f20b6  
```
to a local copy
```
cd 2fdc5693-2858-4b17-96dc-5359432f20b6
composer install
```
## To run tests
```
vendor/phpunit/phpunit/phpunit
```
(sample output)
```
PHPUnit 10.5.2 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: /home/kev/code/2fdc5693-2858-4b17-96dc-5359432f20b6/phpunit.xml

...                                                                 3 / 3 (100%)

Time: 00:00.052, Memory: 6.00 MB

OK (3 tests, 3 assertions)
```
## To run the report
```
php src/report.php
```
(sample output)
```
Please enter the following
Student ID: student1 
Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback): <report-number-by-user> 1
Tony Stark recently completed Numeracy assessment on 16th December 2021 10:46:00 AM
He got 15 questions right out of 16. Details by strand given below:

Measurement and Geometry: 7 out of 7
Number and Algebra: 5 out of 5
Statistics and Probability: 3 out of 4
```