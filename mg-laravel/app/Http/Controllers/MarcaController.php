<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Marca;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $qry = Marca::query();

      if (!empty($request->codmarca)) {
          $qry->where('codmarca', $request->codmarca);
      }

      if (!empty($request->marca)) {
          $qry->where('marca', 'ilike', "%{$request->marca}%");
      }

      switch ($request->sort) {
        case 'codmarca':
          $qry->orderBy('codmarca', 'asc');
          break;

        case '-codmarca':
          $qry->orderBy('codmarca', 'DESC');
          break;

        case '-marca':
          $qry->orderBy('marca', 'DESC');
          break;

        case 'marca':
        default:
          $qry->orderBy('marca', 'ASC');
          break;

      }

      $data = $qry->paginate(50);
        return response()->json(
            $data,
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'marca' => 'required',
        ]);

        $model = new Marca($request->all());
        $model->save();

        return response()->json(
            $model,
            200
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = Marca::findOrFail($id);
        return response()->json(
            $model,
            200
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $model = Marca::findOrFail($id);
        $model->fill($request->all());
        $model->save();

        return response()->json(
            $model,
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            Marca::find($id)->delete();
            $ret = ['resultado' => true, 'mensagem' => 'Marca excluída com sucesso!'];
        }
        catch(\Exception $e){
            $ret = ['resultado' => false, 'mensagem' => 'Erro ao excluir marca!', 'exception' => $e];
        }
        return json_encode($ret);
    }


}
