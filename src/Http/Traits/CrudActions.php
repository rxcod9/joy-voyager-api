<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

class CrudActions
{
    use IndexAction;
    use ShowAction;
    use EditAction;
    use UpdateAction;
    use CreateAction;
    use StoreAction;
    use DestroyAction;
    use RestoreAction;
}
