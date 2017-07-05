<?php echo "<?php\n"; ?>

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\{{ $model }}Repository;

class {{ $model }}Controller extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\{{ $model }}Repository';

}
