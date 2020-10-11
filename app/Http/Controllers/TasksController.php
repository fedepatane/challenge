<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Tasks;
use Validator;
use Illuminate\Support\Facades\Redis;

class TasksController
{

  public function index(Request $req, $size = 5, $column = "title", $sort = "asc"){
    try{
      $search_params = $req->input();
      $t = DB::table('tasks');
      $cache_string = "";

      // searching by all the present params in the request
      foreach ($search_params as $key => $value) {
        $t = $t->where($key, $value); // we accumulate the result
        $t_i = DB::table('tasks')->where($key, $value)->orderBy($column, $sort)->paginate($size)->items(); // we also get the individual result of iteration i to store this result,
        // because in next request, perhaps we have only one filter

        $filter_i = $key . ":" . $value;
        $cache_string = $cache_string. $filter_i; // index to use in the cache to store the result items

        Redis::set('tasks.' . $filter_i , json_encode($t_i));
      }

      // if it is already defined , we return it
      if ($cache_string == "") $cache_string = "all"; // if we have no filter , so we return all data
      if ($tasks = Redis::get('tasks.' . $cache_string)) {
        $tasks = json_decode($tasks, true);
        return response()->json($tasks, 200);
      }

      // if not , we cache it
      $tasks = $t->orderBy($column, $sort)->paginate($size)->items();
      Redis::set('tasks.' . $cache_string , json_encode($tasks));

      return response()->json($tasks, 200);
    }
    catch(\Exception $ex){
      return response()->json($ex->getMessage(), 500);
    }


  }

  public function store(Request $req){

    try{

      $validate = Validator::make($request->all(), [
        'title' => 'required',
        'due_date' => 'required',
      ]);

      $task = new Tasks();
      $task->title = $req->input('title'); // this is mandatory , so i need it is set
      $task->description = (!empty($req->input('description'))) ? ($req->input('description')) : ('') ;
      $task->due_date =  $req->input('due_date'); // this is mandatory , so i need it is set
      $task->updated_at = date("Y/m/d");
      $task->created_at = date("Y/m/d");
      $task->completed = false; // when we create the task, is always false
      $task->save();

      return response()->json(true, 200);
    }
    catch(\Exception $ex){
      return response()->json($ex->getMessage(), 500);
    }


  }

  public function update(Request $req, $id){
    try{
      $task = Tasks::find($id);
      // if the key is not set , so the value remains the same
      $task->title = (!empty($req->input('title'))) ? ($req->input('title')) : ($task->title);
      $task->description = (!empty($req->input('description'))) ? ($req->input('description')) : ($task->description) ;
      $task->due_date = (!empty($req->input('due_date'))) ? ($req->input('due_date')) : ($task->due_date) ;
      $task->updated_at = date("Y/m/d");
      $task->completed = (!empty($req->input('completed'))) ? ($req->input('completed')) : ($task->completed) ;
      $task->save();

      return response()->json(true, 200);
    }
    catch(\Exception $ex){
      return response()->json($ex->getMessage(), 500);
    }
  }

  public function destroy($id){
    try{
      $task = Tasks::find($id);
      $task->delete();
      return response()->json(true, 200);
    }
    catch(\Exception $ex){
      return response()->json($ex->getMessage(), 500);
    }
  }

  public function show($id){
    try{
      // if we have it cached, so we return it
      if ($task = Redis::get('task.id.' . $id)) {
        $task = json_decode($task, true);
        return response()->json($task, 200);
      }

      // if not , we get it from the database and cache it into redis
      $task = Tasks::find($id);
      Redis::set('task.id.' . $id, json_encode($task, true));

      return response()->json($task, 200);
    }
    catch(\Exception $ex){
      return response()->json($ex->getMessage(), 500);
    }
  }

}
