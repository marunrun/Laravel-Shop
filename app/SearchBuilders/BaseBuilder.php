<?php


namespace App\SearchBuilders;


class BaseBuilder
{
    protected $params = [
        'index' => '',
        'type' => '_doc',
        'body' => [
            'query' => [
                'bool' => [
                    'filter' => [],
                    'must' => [],
                ],
            ],
        ],
    ];

    protected $index = 'default';

    public function __construct()
    {
        $this->setIndex($this->index);

        return $this;
    }

    /**
     * 分页查询
     * @param  int  $size  每页的大小
     * @param  int  $page  当前页
     * @return $this
     */
    public function paginate($size, $page)
    {
        $this->params['body']['from'] = ($page - 1) * $size;
        $this->params['body']['size'] = $size;

        return $this;
    }

    public function setIndex(string $name = '')
    {
        $this->params['index'] = $name;

        return $this;
    }

    public function appendFilter(array $filter)
    {
        $this->params['body']['query']['bool']['filter'][] = $filter;

        return $this;
    }

    public function appendMust(array $must)
    {
        $this->params['body']['query']['bool']['must'][] = $must;

        return $this;
    }

    public function minShouldMatch(int $count)
    {
        $this->params['body']['query']['bool']['minimum_should_match'] = $count;

        return $this;
    }

    public function appendShould(array $should)
    {
        $this->params['body']['query']['bool']['should'] = $should;

        return $this;
    }

    public function createAggregate(array $aggregate)
    {
        $this->params['body']['aggs'] = $aggregate;

        return $this;
    }

    public function appendMustNot(array $mustNot)
    {
        if (!isset($this->params['body']['query']['bool']['must_not'])) {
            $this->params['body']['query']['bool']['must_not'] = [];
        }
        $this->params['body']['query']['bool']['must_not'][] = $mustNot;

        return $this;
    }

    /**
     * 排序
     * @param $field
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($field, $direction = 'asc')
    {
        if (!isset($this->params['body']['sort'])) {
            $this->params['body']['sort'] = [];
        }
        $this->params['body']['sort'][] = [$field => $direction];

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
