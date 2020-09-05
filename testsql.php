<?php

    require_once('./init.php');
    use EM\DBAL\Query\Builder;

    $sql = Builder::select('m_user', 'u')
            ->where(['a', 'IN', ['a', 'b', 'c', 'd']])
            ->join('m_address', ['left column', '=', 'right column'])
            ->andOn(['a', '=', 'b'])
            // ->orOn(['aeiei', '<=', '200'])
            // ->join('m_city', ['2 left column', '=', '2 right column'])
            ->columns(['a' => 'hello', 'b' => 'test', 'c' => '123'])
            ->avg('text', 'help')
            ->if(['a', 'b', 'c', 'd'])
            ->case(['jee', '>', '100', 'This is a test'])
            ->case(['yee', '<>', '300', 'Three hundred'])
            ->case(['dee', '!=', '200', 300])
            ->orderBy(['Column DESC'])
            // ->where(['column', '=', 'test"s'])
            // ->where(['test', '>=', '10'])
            ->where(['a', 'like', 'teeee'])
            ->andWhere(['beee', '>', '100'])
            ->offsetLimits(100, 100)
            ->orWhere(['b', 'like', '02020202']);
    print $sql . "\n";

    $sql = Builder::update('m_user', 'u')
            ->columns(['a' => 'hello ', 'b' => 'test ', 'c' => ' 123 '])
            ->where(['a', 'like', 'teeee'])
            ->setLimit(10);
    print $sql . "\n";

    $sql = Builder::delete('m_user', 'u')
            ->where(['b', '>', 20]);
            // ->setLimit(10);
    print $sql . "\n";

    $sql = Builder::insert('m_user', 'u')
            ->columns(['a' => 'hello ', 'b' => 'test ', 'c' => ' 123 ']);
    print $sql . "\n";