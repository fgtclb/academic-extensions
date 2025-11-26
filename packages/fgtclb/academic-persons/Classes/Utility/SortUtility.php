<?php declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Utility;

class SortUtility
{
    /**
     * @param array<string, mixed>|object[] $array
     * @param int[] $idList
     *
     * @return array<string, mixed>|object[]>
     */
    public static function sortArrayByIdList(array $array, array $idList): array
    {
        $sorted = [];
        foreach ($idList as $uid) {
            foreach ($array as $item) {
                // ToDo: maybe check object type
                if (is_object($item) && method_exists($item, 'getUid')) {
                    if ($item->getUid() === $uid) {
                        $sorted[] = $item;
                    }
                } else if (is_array($item) && isset($item['uid'])) {
                    if ($item['uid'] === $uid) {
                        $sorted[] = $item;
                    }
                }
            }
        }

        return $sorted;
    }
}
