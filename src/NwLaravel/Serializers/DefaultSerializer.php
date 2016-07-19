<?php

namespace NwLaravel\Serializers;

use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Pagination\PaginatorInterface;

/**
 * Class DefaultSerializer
 */
class DefaultSerializer extends DataArraySerializer
{
    /**
     * Serialize an item.
     *
     * @param string $resourceKey String Resource Key
     * @param array  $data        Array Data
     *
     * @return array
     */
    public function item($resourceKey, array $data)
    {
        return $resourceKey ? $data : array('data' => $data);
    }

    /**
     * Serialize the paginator.
     *
     * @param PaginatorInterface $paginator Paginator Interface
     *
     * @return array
     */
    public function paginator(PaginatorInterface $paginator)
    {
        $currentPage = (int) $paginator->getCurrentPage();
        $lastPage = (int) $paginator->getLastPage();

        $pagination = array(
            'total' => (int) $paginator->getTotal(),
            'count' => (int) $paginator->getCount(),
            'per_page' => (int) $paginator->getPerPage(),
            'current_page' => $currentPage,
            'total_pages' => $lastPage,
        );

        return array('pagination' => $pagination);
    }
}
