<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use App\User; 
use App\UserDetails_model;
use Illuminate\Support\Facades\Auth; 
use Validator;
class UploadFileController extends Controller 
{

    public function images(Request $request){
        if(!$request->hasFile('image')) {
            return response()->json(['upload file not found'], 400);
        }
        $file = $request->file('image');
        if(!$file->isValid()) {
            return response()->json(['invalid file upload'], 400);
        }
        $path = public_path() . '/images/';
        $file->move($path, $file->getClientOriginalName());
        return response()->json(compact('path'));

    }
    public function videos(Request $request)
    {
     
      $file = $request->file('video');
      $file = $file->getClientOriginalName();
      $path = public_path(). '/video/';
      return response()->json(compact('path'));
      
  }
  public function store(Request $request)
  {
    $user_record = new UserDetails_model();
    $user_record->first_name = $request->input('first_name');
    $user_record->last_name = $request->input('last_name');
    $user_record->email = $request->input('email');
    $user_record->password = $request->input('password');
    $user_record->save();
    return response()->json($user_record);
  }
  public function showrecord(){
    $view_record = UserDetails_model::all();
    return response()->json($view_record);
  }
  public function remove(Request $request, $id)
  {
    $delete_record = UserDetails_model::find($id);
    $delete_record->delete();
    return response()->json($delete_record);
  }
  public function update(Request $request, $id)
  {
    $record_update = UserDetails_model::find($id);
    $record_update->first_name = $request->input('first_name');
    $record_update->last_name = $request->input('last_name');
    $record_update->email = $request->input('email');
    $record_update->password = $request->input('password');
    $record_update->save();
    return response()->json($record_update);
  }

}