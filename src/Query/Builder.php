<?php

namespace Stevebauman\Wmi\Query;

use Stevebauman\Wmi\Exceptions\Query\InvalidFromStatement;
use Stevebauman\Wmi\Query\Expressions\From;
use Stevebauman\Wmi\Query\Expressions\Where;
use Stevebauman\Wmi\Query\Expressions\Select;
use Stevebauman\Wmi\ConnectionInterface;

class Builder implements BuilderInterface
{
    /**
     * The select statements of the current query.
     *
     * @var Select
     */
    protected $select;

    /**
     * The from statement of the current query.
     *
     * @var From
     */
    protected $from;

    /**
     * The where statements of the current query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The and where statements of the current query.
     *
     * @var array
     */
    protected $andWheres = [];

    /**
     * The or where statements of the current query.
     *
     * @var array
     */
    protected $orWheres = [];

    /**
     * The current connection.
     *
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Adds columns to the select query statement.
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function select($columns)
    {
        $this->select = new Select($columns);

        return $this;
    }

    /**
     * Adds a where expression to the current query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return $this
     */
    public function where($column, $operator, $value = null)
    {
        if(count($this->wheres) > 0) {
            $this->andWhere($column, $operator, $value);
        }

        $this->wheres[] = new Where($column, $operator, $value);

        return $this;
    }

    /**
     * Adds an and where expression to the current query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return $this
     */
    public function andWhere($column, $operator, $value = null)
    {
        $this->andWheres[] = new Where($column, $operator, $value, 'AND');

        return $this;
    }

    /**
     * Adds a or where statement to the current query.
     *
     * @param $column
     * @param $operator
     * @param mixed $value
     *
     * @return $this
     */
    public function orWhere($column, $operator, $value = null)
    {
        $this->orWheres[] = new Where($column, $operator, $value, 'OR');

        return $this;
    }

    /**
     * Adds a from statement to the current query.
     *
     * @param string $namespace
     *
     * @return $this
     */
    public function from($namespace)
    {
        $this->from = new From($namespace);

        return $this;
    }

    /**
     * Builds and executes the current
     * query, returning the results.
     *
     * @return mixed
     */
    public function get()
    {
        $query = $this->buildQuery();

        return $this->connection->query($query);
    }

    /**
     * Returns the current query.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->buildQuery();
    }

    /**
     * Returns the current select expression.
     *
     * @return null|Select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Returns the where expressions on the current query.
     *
     * @return array
     */
    public function getWheres()
    {
        return $this->wheres;
    }

    /**
     * Builds the query and returns the query string.
     *
     * @return string
     */
    private function buildQuery()
    {
        $select = $this->buildSelect();

        $from = $this->buildFrom();

        $wheres = $this->buildWheres();

        $query = sprintf('%s %s %s', $select, $from, $wheres);

        return trim($query);
    }

    /**
     * Builds the select statement on the current query.
     *
     * @return string
     */
    private function buildSelect()
    {
        if($this->select instanceof Select) {
            return $this->select->build();
        } else {
            return (new Select())->build();
        }
    }

    /**
     * Builds the from statement on the current query.
     *
     * @return string
     *
     * @throws InvalidFromStatement
     */
    private function buildFrom()
    {
        if($this->from instanceof From) {
            return $this->from->build();
        }

        $message = 'No from statement exists. You need to supply one to retrieve results.';

        throw new InvalidFromStatement($message);
    }

    /**
     * Builds the wheres on the current query
     * and returns the result query string.
     *
     * @return string
     */
    private function buildWheres()
    {
        $statement = '';

        foreach($this->wheres as $where) {
            $statement = $where->build();
        }

        foreach($this->andWheres as $andWhere) {
            $statement .= $andWhere->build();
        }

        foreach($this->orWheres as $orWhere) {
            $statement .= $orWhere->build();
        }

        return $statement;
    }
}
