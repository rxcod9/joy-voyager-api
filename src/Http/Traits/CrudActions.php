<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

use Joy\VoyagerCore\Http\Controllers\Traits\InsertUpdateData;

trait CrudActions
{
    use IndexAction;
    use ShowAction;
    use CreateAction;
    use StoreAction;
    use EditAction;
    use UpdateAction;
    use SingleUpdateAction;
    use DestroyAction;
    use RestoreAction;
    use InsertUpdateData;
}
