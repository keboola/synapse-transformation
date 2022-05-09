<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests;

use Keboola\SynapseTransformation\QueryFormatter;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryFormatterTest extends TestCase
{
    private QueryFormatter $queryFormatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryFormatter = new QueryFormatter();
    }

    /**
     * @dataProvider removeCommentsSqlProvider
     */
    public function testRemoveComments(string $expectedOutput, string $input): void
    {
        Assert::assertSame($expectedOutput, $this->queryFormatter->removeComments($input));
    }

    /**
     * @dataProvider formatToLogProvider
     */
    public function testFormatToLog(string $expectedOutput, string $input): void
    {
        Assert::assertSame($expectedOutput, $this->queryFormatter->formatToLog($input));
    }

    public function removeCommentsSqlProvider(): array
    {
        return [
            'simple' => [
                "SELECT \n 1",
                "SELECT \n 1",
            ],
            'emoji' => [
                "\u{1F30F}",
                "\u{1F30F}",
            ],
            'comment' => [
                "SELECT \n \n 2",
                "SELECT \n -- COMMENT\n 2",
            ],
            // # is NOT comment in the Synapse
            'temp-table' => [
                'SELECT * INTO #temp_table FROM SOURCE_TABLE',
                "-- COMMENT\nSELECT * INTO #temp_table FROM SOURCE_TABLE",
            ],
        ];
    }

    public function formatToLogProvider(): array
    {
        return [
            'simple' => [
                'SELECT 1',
                "SELECT \n  1",
            ],
            'emoji' => [
                "\u{1F30F}",
                "\u{1F30F}",
            ],
            'long' => [
                /** @lang text */
                // phpcs:disable
                <<<EOF
SELECT e.employee_id AS "Employee #" , e.first_name || ' ' || e.last_name AS "Name" , e.email AS "Email" , e.phone_number AS "Phone" , TO_CHAR(e.hire_date, 'MM/DD/YYYY') AS "Hire Date" , TO_CHAR(e.salary, 'L99G999D99', 'NLS_NUMERIC_CHARACTERS = ''.,'' NLS_CURRENCY = ''$''') AS "Salary" , e.commission_pct AS "Comission %" , 'works as ' || j.job_title || ' in ' || d.department_name || ' department (manager: ' || dm.first_name || ' ' || dm.last_name || ') and immediate supervisor: ' || m.first_name
...
oyee_id -- to get name of location LEFT JOIN locations l ON d.location_id = l.location_id LEFT JOIN countries c ON l.country_id = c.country_id LEFT JOIN regions r ON c.region_id = r.region_id -- to get job history of employee LEFT JOIN job_history jh ON e.employee_id = jh.employee_id -- to get title of job history job_id LEFT JOIN jobs jj ON jj.job_id = jh.job_id -- to get namee of department from job history LEFT JOIN departments dd ON dd.department_id = jh.department_id ORDER BY e.employee_id;
EOF,
                // phpcs:enable
                /** @lang text */
                <<<EOF
SELECT
  e.employee_id AS "Employee #"
  , e.first_name || ' ' || e.last_name AS "Name"
  , e.email AS "Email"
  , e.phone_number AS "Phone"
  , TO_CHAR(e.hire_date, 'MM/DD/YYYY') AS "Hire Date"
  , TO_CHAR(e.salary, 'L99G999D99', 'NLS_NUMERIC_CHARACTERS = ''.,'' NLS_CURRENCY = ''$''') AS "Salary"
  , e.commission_pct AS "Comission %"
  , 'works as ' || j.job_title || ' in ' || d.department_name || ' department (manager: '
    || dm.first_name || ' ' || dm.last_name || ') and immediate supervisor: ' || m.first_name || ' ' || m.last_name
  , TO_CHAR(j.min_salary, 'L99G999D99', 'NLS_NUMERIC_CHARACTERS = ''.,'' NLS_CURRENCY = ''$''') || ' - ' ||
      TO_CHAR(j.max_salary, 'L99G999D99', 'NLS_NUMERIC_CHARACTERS = ''.,'' NLS_CURRENCY = ''$''') AS "Current Salary"
  , l.street_address || ', ' || l.postal_code || ', ' || l.city || ', ' || l.state_province || ', '
    || c.country_name || ' (' || r.region_name || ')' AS "Location"
  , jh.job_id AS "History Job ID"
  , 'worked from ' || TO_CHAR(jh.start_date, 'MM/DD/YYYY') || ' to ' || TO_CHAR(jh.end_date, 'MM/DD/YYYY') ||
    ' as ' || jj.job_title || ' in ' || dd.department_name || ' department' AS "History Job Title"
  
FROM employees e
-- to get title of current job_id
  JOIN jobs j 
    ON e.job_id = j.job_id
-- to get name of current manager_id
  LEFT JOIN employees m 
    ON e.manager_id = m.employee_id
-- to get name of current department_id
  LEFT JOIN departments d 
    ON d.department_id = e.department_id
-- to get name of manager of current department
-- (not equal to current manager and can be equal to the employee itself)
  LEFT JOIN employees dm 
    ON d.manager_id = dm.employee_id
-- to get name of location
  LEFT JOIN locations l
    ON d.location_id = l.location_id
  LEFT JOIN countries c
    ON l.country_id = c.country_id
  LEFT JOIN regions r
    ON c.region_id = r.region_id
-- to get job history of employee
  LEFT JOIN job_history jh
    ON e.employee_id = jh.employee_id
-- to get title of job history job_id
  LEFT JOIN jobs jj
    ON jj.job_id = jh.job_id
-- to get namee of department from job history
  LEFT JOIN departments dd
    ON dd.department_id = jh.department_id

ORDER BY e.employee_id;
EOF,
            ],
        ];
    }
}
