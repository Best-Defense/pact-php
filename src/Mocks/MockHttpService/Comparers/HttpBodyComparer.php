<?php

namespace PhpPact\Mocks\MockHttpService\Comparers;

use PhpPact\Comparers\DiffComparisonFailure;
use PhpPact\Matchers\Checkers\FailedMatcherCheck;
use PhpPact\Comparers\ComparisonResult;

class HttpBodyComparer
{

    /**
     * @param $expected \PhpPact\Mocks\MockHttpService\Models\IHttpMessage
     * @param $actual \PhpPact\Mocks\MockHttpService\Models\IHttpMessage
     *
     * @return \PhpPact\Comparers\ComparisonResult
     */
    public function compare($expected, $actual)
    {
        $bodyMatcherCheckers = $expected->getBodyMatchers();
        $expectedContentType = $expected->getContentType();
        $matchingRules = $expected->getMatchingRules();

        $result = new ComparisonResult("has a body");

        if ($expected->shouldSerializeBody() && $expected->getBody() == null && $actual->getBody()) {
            $result->recordFailure(new DiffComparisonFailure($expected, $actual));
            return $result;
        }

        if ($expected->getBody() == null) {
            return $result;
        }

        // looking for an exact match at the object level
        if ($expectedContentType=="application/json") {
            if (is_string($expected)) {
                $expected = $this->jsonDecode($expected);
            } elseif (method_exists($expected, "getBody") && is_string($expected->getBody())) {
                $expected = $this->jsonDecode($expected->getBody());
            }

            if (is_string($actual)) {
                $actual = $this->jsonDecode($actual);
            } elseif (method_exists($actual, "getBody") && is_string($actual->getBody())) {
                $actual = $this->jsonDecode($actual->getBody());
            }
        }

        // cycle through matching rules
        foreach ($bodyMatcherCheckers as $bodyMatcherCheckerKey => $bodyMatcherChecker) {

            /**
             * @var $bodyMatcherChecker \PhpPact\Matchers\Checkers\IMatchChecker
             */
            $results = $bodyMatcherChecker->match($bodyMatcherCheckerKey, $expected, $actual, $matchingRules);

            /**
             * @var $results \PhpPact\Matchers\Checkers\MatcherResult
             */
            $checks = $results->getMatcherChecks();
            foreach ($checks as $check) {
                if (($check instanceof FailedMatcherCheck)) {
                    $result->recordFailure(new DiffComparisonFailure($expected, $actual));
                }
            }
        }

        return $result;
    }

    /**
     * Wrapper function to decode an object to JSON
     * @param $obj
     * @return mixed
     */
    private function jsonDecode($obj)
    {
        $json = \json_decode($obj);
        if ($json !== null) {
            $obj = $json;
        }
        return $obj;
    }

}
