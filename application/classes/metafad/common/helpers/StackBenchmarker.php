<?php

/**
 * A stack benchmarker is a LIFO stopwatch used to record multiple stopwatch in nested benchmarks.
 *
 * It relies on microtime() and it's intended to be used for seconds-long operations as this class
 * has a resolution of about a millisecond.
 */
class metafad_common_helpers_StackBenchmarker
{
    private $bench;
    private $begin;

    private static $elapsedKeyword = 'elapsed';

    private function & ref(&$array, $field){
        return $array[$field];
    }

    private function & refExp(&$expr){
        return $expr;
    }

    /**
     * StackBenchmarker constructor.
     *
     * It uses a LIFO benchmarking, for nested benchmarks.
     */
    public function __construct()
    {
        $this->bench = new stdClass();
        $this->begin = array();
    }

    /**
     * It begins a new benchmark to be recorded.
     */
    public function pushBench(){
        $this->begin[] = microtime(true);
    }

    /**
     * It pops an existing benchmark from the stack by recording the elapsed time.
     *
     * This method does nothing if:
     * - No list of string is provided
     * - No pending benchmarks are present
     * - The provided list of string contains an 'elapsed' (reserved word)
     *
     * ---
     *
     * The usage must follow the same rules of the parenthesis:
     *
     * A -> push . pop | push . A . pop | A . A
     *
     * ---
     * ---
     *
     * Usage Examples:
     *
     * pushBench();
     *
     * popBench('z'); -> It records a benchmark into $benchmarks->z->elapsed
     *
     * popBench() -> It does nothing, no pending benchmarks;
     *
     * ---
     *
     * pushBench();
     *
     * popBench(); -> It does nothing, no strings provided;
     *
     * ---
     *
     * pushBench();
     *
     * popBench('a', 'b'); -> It records a benchmark into $benchmarks->a->b->elapsed
     *
     * ---
     *
     * pushBench();
     *
     * pushBench();
     *
     * popBench('a', 'b'); -> It records a benchmark into $benchmarks->a->b->elapsed
     *
     * pushBench();
     *
     * popBench('b'); -> It records a benchmark into $benchmarks->b->elapsed
     *
     * popBench('c', 'd', 'e'); -> It records a benchmark into $benchmarks->c->d->e->elapsed
     *
     * ---
     *
     * pushBench();
     *
     * pushBench();
     *
     * pushBench('a'); -> It records a benchmark into $benchmarks->a->elapsed
     *
     * pushBench('a'); -> It records a benchmark into $benchmarks->a->elapsed, therefore, the previous benchmark IS OVERWRITTEN
     */
    public function popBench(){
        if (count($this->begin) == 0 || func_num_args() == 0 || in_array(self::$elapsedKeyword, func_get_args()))
            return;
        else{
            $time = microtime(true);
            $num = func_num_args();

            $ptr = $this->bench;
            for($i = 0; $i < $num - 1; $i++){
                $section = func_get_arg($i);
                if (is_null($ptr->{$section})){
                    $ptr->{$section} = new stdClass();
                }
                $ptr = $ptr->{$section};
            }

            if (is_null($ptr->{func_get_arg($num - 1)})){
                $ptr->{func_get_arg($num - 1)} = new stdClass();
            }
            $ptr = $ptr->{func_get_arg($num - 1)};

            $ptr->{self::$elapsedKeyword} = round(($time - array_pop($this->begin)) * 1000, 3);
        }
    }

    /**
     * It returns the array containing all the recorded benchmark at the moment of the call.
     *
     * Every value is given in Milliseconds with 3 decimal digits (ms.xxx, where xxx are thousandths of millisecond).
     *
     * @param $returnAsArray bool false for returning a stdClass, true for returning an associative-array
     * @return array The array containing the recorded benchmark recorded from the Benchmarker initialization to the moment of this call
     */
    public function getBenchmark($returnAsArray = true){
        $ret = $this->bench;
        return json_decode(json_encode($ret), $returnAsArray);
    }
}