<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Cms;
use App\Models\CreatorGroup;
use App\Models\CreatorGroupChapter;
use App\Models\CreatorGroupNote;
use App\Models\CreatorSubjectNote;
use App\Models\CustomerChosenSubject;
use App\Models\FavoriteNote;
use App\Models\Group;
use App\Models\Note;
use App\Models\Subject;
use App\Models\Suggestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule as ValidationRule;

class ApiController extends Controller
{


    /**
     * @Function:        <getSubjectList>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <03-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <03-08-2022>
     * @Description:     <This function works for getting Subject List>
     * @return \Illuminate\Http\Response
     */
    public function getSubjectList(){
        try{
            $subject = Subject::select('id', 'subject_name')->where(['status' => 1])->get();
            return response()->json(['success' => true, 'data' => ['result' => $subject ,'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <userSuggestion>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <03-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <03-08-2022>
     * @Description:     <This function works for user suggestions>
     * @return \Illuminate\Http\Response
     */
    public function userSuggestion(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'subject_name' => ['required', 'alpha_dash'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }

            $data = request(['subject_name']);
            $data['user_id'] = auth()->guard('customer-api')->user()->id;
            $suggestion = Suggestion::create($data);

            return response()->json(['success' => true, 'data' => [ 'suggestion' => $suggestion, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <createGroupNotes>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <04-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <12-08-2022>
     * @Description:     <This function works for add notes into chapter>
     * @return \Illuminate\Http\Response
     */
    public function createGroupNotes(Request $request, $chapter_id){
        try{
            $rules = array(
                'group_id' => ['required'],
                'is_public' => ['required'],
                'note_name' => ['required', 'regex:/^[a-zA-Z0-9\s\-\_]+$/'],
                'is_file' => ['required'],
                'text' => ['required_if:is_file,==,0'],
                'file' => ['required_if:is_file,==,1']
            );
            $messages = array(
                'group_id.required' => 'Group id is required.',
                'is_public.required' => 'is public  required.',
                'note_name.required' => 'Note name is required.',
                'is_file.required' => 'file is required.',
                'text.required_if' => 'Text is required.',
                'file.required_if' => 'Image is required.',
            );
            $validator = Validator::make($request->all(), $rules, $messages );
            if ( $validator->fails() ) 
            {
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }

            $data = request(['note_name', 'is_file', 'text', 'file']);
            $data['note_name'] = $request->note_name;
            if($request->is_file == 0){
                $data['text'] = $request->text;
            }
            else{
                if($request->file('file')){
                    $filenameWithExt = $request->file('file')->getClientOriginalName();
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $request->file('file')->getClientOriginalExtension();
                    $fileNameToStore = $filename.'_'.time().'.'.$extension;
                    $path = $request->file('file')->storeAs('public/files', $fileNameToStore);
                }
                $data['file'] = $fileNameToStore;
            }
            $notes = Note::create($data);

            $createGroupNotes = request(['group_id', 'chapter_id', 'notes_id', 'is_public']);
            $createGroupNotes['creator_id'] = auth()->guard('customer-api')->user()->id;
            $createGroupNotes['group_id'] = $request->group_id;
            $createGroupNotes['chapter_id'] = $chapter_id; //$request->chapter_id;
            $createGroupNotes['notes_id'] = $notes->id;
            $createGroupNotes['is_public'] = $request->is_public;

            $groupNotesData = CreatorGroupNote::create($createGroupNotes);

            return response()->json(['success' => true, 'data' => [ 'groupNotesData'=>$groupNotesData, 'Notes' => $notes, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }
   
     /**
     * @Function:        <getUserSelectedSubject>
     * @Author:          Vrunda Parekh( Sixty13 )
     * @Created On:      <06-09-2022>
     * @Last Modified By:Vrunda Parekh
     * @Last Modified:   <06-09-2022>
     * @Description:     <This function works for get user selected subjects>
     * @return \Illuminate\Http\Response
     */
    public function getUserSelectedSubject(){
        try{
            $subject  = CustomerChosenSubject::select('subject_id', 'customer_id')->where('customer_id', auth()->guard('customer-api')->user()->id)->pluck('subject_id');
            return response()->json(['success' => true, 'data' => ['result' => $subject,'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <createGroup>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <05-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <05-08-2022>
     * @Description:     <This function works for creating group>
     * @return \Illuminate\Http\Response
     */
    public function createGroup(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'group_name' => ['required', 'regex:/^[a-zA-Z0-9\s\-\_]+$/'],
                'is_public' => ['required'],
                'group_code' => ['required|unique:group_code']
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            
            $data = request(['creators_id', 'group_name', 'is_public', 'group_code']);
            $data['creators_id'] = auth()->guard('customer-api')->user()->id;
            $data['group_name'] = $request->group_name;
            $data['is_public'] = $request->is_public;
            $data['group_code'] = mt_rand(100000, 999999);
            do{
                $data['group_code'] = mt_rand(100000, 999999);
            } while (Group::where('group_code', $data['group_code'])->exists());

            $group = Group::create($data);
            $group = Group::with('creator')->where('group_name', $request->group_name)->get();

            return response()->json(['success' => true, 'data' => [ 'group' => $group, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <createChapters>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <05-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <05-08-2022>
     * @Description:     <This function works for add chapters>
     * @return \Illuminate\Http\Response
     */
    public function createChapters(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'group_id' => ['required'],
                'chapter_name' => ['required', 'regex:/^[a-zA-Z0-9\s\-\_]+$/'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $chapter = CreatorGroupChapter::where(['group_id' => $request->group_id, 'chapter_name' => $request->chapter_name])->exists();
            if($chapter){
                return response()->json(['success' => false, 'error' => $this->validationMessage(['message' => ['Chapter name already exists']])], 422);
            }
            $data = request(['group_id', 'chapter_name']);
            $data['creator_id'] = auth()->guard('customer-api')->user()->id;
            $data['chapter_name'] = $request->chapter_name;
            $data['group_id'] = $request->group_id;

            $groupChapter = CreatorGroupChapter::create($data);

            return response()->json(['success' => true, 'data' => [ 'chapter' => $groupChapter, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <createSubjectNotes>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <05-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <05-08-2022>
     * @Description:     <This function works for create subject notes>
     * @return \Illuminate\Http\Response
     */
    public function createSubjectNotes(Request $request){
        try{

            $rules = array(
                'subject_id' => ['required'],
                'is_public' => ['required'],
                'note_name' => ['required', 'regex:/^[a-zA-Z0-9\s\-\_]+$/'],
                'is_file' => ['required'],
                'text' => ['required_if:is_file,==,0'],
                'file' => ['required_if:is_file,==,1']
            );    
            $messages = array(
                            'subject_id.required' => 'Subject is required.',
                            'is_public.required' => 'public  required.',
                            'note_name.required' => 'Note name is required.',
                            'is_file.required' => 'file is required.',
                            'text.required_if' => 'Text is required.',
                            'file.required_if' => 'Image is required.',

                        );
            $validator = Validator::make($request->all(), $rules, $messages );
            if ( $validator->fails() ) 
            {
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
                    
            $data = request(['note_name', 'is_file', 'text', 'file']);
            $data['note_name'] = $request->note_name;
            if($request->is_file == 0){
                $data['text'] = $request->text;
            }
            else{
                if($request->file('file')){
                    $filenameWithExt = $request->file('file')->getClientOriginalName();
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $request->file('file')->getClientOriginalExtension();
                    $fileNameToStore = $filename.'_'.time().'.'.$extension;
                    $path = $request->file('file')->storeAs('public/files', $fileNameToStore);
                }
                $data['file'] = $fileNameToStore;
            }
            $notes = Note::create($data);

            $createSubjectNotes = request(['subject_id', 'notes_id', 'is_public']);
            $createSubjectNotes['creator_id'] = auth()->guard('customer-api')->user()->id;
            $createSubjectNotes['subject_id'] = $request->subject_id;
            $createSubjectNotes['notes_id'] = $notes->id;
            $createSubjectNotes['is_notes_public'] = $request->is_public;

            $subjectNotesData = CreatorSubjectNote::create($createSubjectNotes);
            
            return response()->json(['success' => true, 'data' => [ 'groupNotesData'=>$subjectNotesData, 'Notes' => $notes, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <customerChosenSubjects>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <06-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <06-08-2022>
     * @Description:     <This function works for add subject chosen by customer>
     * @return \Illuminate\Http\Response
     */
    public function customerChosenSubjects(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'subject_id' => ['required'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            
            $data = request(['subject_id']);
            $subject_id[] = $request->subject_id;
            $subjects = explode(',', $request->subject_id);
            foreach($subjects as $subject){
                $data['customer_id'] = auth()->guard('customer-api')->user()->id;
                $data['subject_id'] = $subject;
                CustomerChosenSubject::firstOrCreate($data);
            }
            CustomerChosenSubject::where('customer_id', $data['customer_id'])->whereNotIn('subject_id', $subjects)->delete();
            return response()->json(['success' => true, 'data' => ['message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <searchSubjectName>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <06-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <06-08-2022>
     * @Description:     <This function works for search subject by name>
     * @return \Illuminate\Http\Response
     */
    public function searchSubjectName(Request $request){
        try{
            $search = $request->search;
            $result = Subject::where('subject_name', 'LIKE', '%'.$search.'%')->get();

            if(count($result)){
                return response()->json(['success' => true, 'data' => [ 'search' => $result, 'message' => 'The data was successfully retrieved.']], 200);
            }else{
                return response()->json(['Result' => 'Data not found'], 404);
            }
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <searchGroupName>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <08-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for search group by name>
     * @return \Illuminate\Http\Response
     */
     public function searchGroupName(Request $request){
        try{
            $search = $request->search;
            $result = Group::where('group_name', 'LIKE', '%'.$search.'%')->get();

            if(count($result)){
                return response()->json(['success' => true, 'data' => [ 'search' => $result, 'message' => 'The data was successfully retrieved.']], 200);
            }else{
                return response()->json(['Result' => 'Data not found'], 404);
            }
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
     }

     /**
     * @Function:        <getGroupList>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <08-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for getting group list>
     * @return \Illuminate\Http\Response
     */
    public function getGroupList(){
        try{
            $userGroup = Group::where(['creators_id' => auth()->guard('customer-api')->user()->id])->with('creator')->get();
            $group = Group::where('creators_id', '!=', auth()->guard('customer-api')->user()->id)->with('creator')->get();
            return response()->json(['success' => true, 'data' => ['userGroup' => $userGroup, 'group' => $group, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <searchPrivateGroup>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <08-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for search private group via group code >
     * @return \Illuminate\Http\Response
     */
    public function searchPrivateGroup(Request $request){
        try{
            $search = $request->search;
            $result = Group::where(['is_public' => 0])->where('group_code', $search)->get();
            if(count($result)){
                return response()->json(['success' => true, 'data' => [ 'search' => $result, 'message' => 'The data was successfully retrieved.']], 200);
            }else{
                return response()->json(['Result' => 'Data not found'], 200);
            }
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <getNotesDetails>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <08-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for getting notes details >
     * @return \Illuminate\Http\Response
     */
    public function getNotesDetails($id){
        try{
            $notesDetails = Note::where(['id' => $id])->with(['isFavorite' => function ($query){
                $query->where('creator_id', auth()->guard('customer-api')->user()->id);
            }])->first();
            $noteCount = $notesDetails->counter + 1;
            Note::where('id', $id)->update(['counter' => $noteCount]);
            return response()->json(['success' => true, 'data' => ['result' => $notesDetails ,'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <deleteMyNote>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <08-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for delete user notes >
     * @return \Illuminate\Http\Response
     */
    public function deleteMyNote(CreatorGroupChapter $chapter_id, Note $notes_id = NULL){
        try{
            $chapter = $chapter_id->id;
            if($notes_id){
                $notes_id = $notes_id->id;
                CreatorGroupNote::where(['notes_id' => $notes_id])->delete();
                CreatorGroupChapter::where(['id' => $chapter])->delete();
                $notesDetails = Note::where(['id' => $notes_id])->delete();
                return response()->json(['success' => true, 'data' => ['message' => 'The note was successfully deleted.']], 200);
            }
            else{
                $chapterGroup = CreatorGroupChapter::where(['id' => $chapter])->first();
                if($chapterGroup)
                {
                    $notes_id = CreatorGroupNote::where('chapter_id', $chapter)->pluck('notes_id');
                    $notesDetails = Note::whereIn('id',  $notes_id)->delete();
                    $chapterGroup->delete();
                }
                return response()->json(['success' => true, 'data' => ['message' => 'The chapter was successfully deleted.']], 200);
            }

        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

     /**
     * @Function:        <deleteSubjectNote>
     * @Author:          Vrunda Parekh( Sixty13 )
     * @Created On:      <31-07-2022>
     * @Last Modified By:Vrunda Parekh
     * @Last Modified:   <08-08-2022>
     * @Description:     <This function works for delete user notes >
     * @return \Illuminate\Http\Response
     */
    public function deleteSubjectNote($notes_id){
        try{
            if($notes_id){
                CreatorSubjectNote::where(['notes_id' => $notes_id])->delete();
                FavoriteNote::where(['note_id' => $notes_id])->delete();
                $notesDetails = Note::where(['id' => $notes_id])->delete();
                return response()->json(['success' => true, 'data' => ['message' => 'The note was successfully deleted.']], 200);
            }

        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <addToFavorite>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <09-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <09-08-2022>
     * @Description:     <This function works for add notes to favorite >
     * @return \Illuminate\Http\Response
     */
    public function addToFavorite(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'note_id' => ['required']
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $data = request(['note_id']);
            $data['creator_id'] = auth()->guard('customer-api')->user()->id;
            $favorite = FavoriteNote::where(['note_id' => $request->note_id, 'creator_id' => $data['creator_id']])->first();
            if($favorite){
                $favorite->delete();
                return response()->json(['success' => true, 'data' => [ 'message' => 'The note removed from favorite successfully.']], 200);
            }else{
               $addToFavorite = FavoriteNote::create($data);
                $favArray = [];
                $addToFavorite = FavoriteNote::create($data);
              
                $addToFavorite['favorite'] = false;
                $addToFavorite['favoritenote'] = true;
                $favArray = 
                array_push($a,"blue","yellow");
                print_r($addToFavorite);
                die;
                foreach($addToFavorite as $k=>$fav){
                    $favArray[$k] = $fav;
                    $favArray[$k]['favorite'] = false;
                }
                
                return response()->json(['success' => true, 'data' => [ 'addToFavorite' => $addToFavorite, 'message' => 'The note added to favorite successfully.']], 200);
            } 
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <getFavNotesList>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <09-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <09-08-2022>
     * @Description:     <This function works for get all favorite notes list >
     * @return \Illuminate\Http\Response
     */
    public function getFavNotesList(){
        try{
            $creator_id = auth()->guard('customer-api')->user()->id;
            $favArray = [];
            $favNotes = FavoriteNote::select('creator_id', 'note_id')->with('notes','creator')->where('creator_id', $creator_id)->get();
            foreach($favNotes as $k=>$fav){
                $favArray[$k] = $fav;
                $favArray[$k]['is_favorite'] = true;
            }
            // $favArray = array_push($fav);
            // dd($favArray);
            return response()->json(['success' => true, 'data' => ['favoriteNotes' => $favArray ,'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <getHomeScreen>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <12-08-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <15-08-2022>
     * @Description:     <This function works for get home screen >
     * @return \Illuminate\Http\Response
     */

    public function getHomeScreen(){   
        try{
            $customer = auth()->guard('customer-api')->user()->id;
            $chosenSubject = CustomerChosenSubject::where(['customer_id' => $customer])->groupBy('subject_id')->pluck('subject_id');
            
            if(count($chosenSubject) == 0) {
                return response()->json(['success' => false, 'error' => ['message' => 'Please choose subject first',]], 422);
            }
 
            $chosenSubject_val = CreatorSubjectNote::whereIn('subject_id', $chosenSubject)->get();
            $chosenSubjectNotes = array();
            foreach($chosenSubject_val as $sub)
            {
                // if($sub->creator_id == $customer){
                   $chosenSubjectNotes[] = CreatorSubjectNote::where('is_notes_public',1)->whereIn('subject_id',$chosenSubject)
                    ->pluck('notes_id');
                //  }else{
                    // if($sub->is_subject_public == 1){
                        $chosenSubjectNotes[] = CreatorSubjectNote::where('is_notes_public',1)->whereIn('subject_id',$chosenSubject)
                        ->where('is_subject_public',1)
                        ->pluck('notes_id');
                    // }else{
                        // $chosenSubjectNotes[] = '';
                       
                    // }
                   
                // }
                // print_r($chosenSubjectNotes);
                // die;
                $recent = Note::with('creator_subject')->where('created_at','>=', Carbon::today()->subDays(7)->toDateTimeString())->whereIn('id', $chosenSubjectNotes)->get();

                $recommended = Note::with('creator_subject')->whereIn('id', $chosenSubjectNotes)->orderBy('counter', 'desc')->get();
            
                $browse = Note::with('creator_subject')->whereIn('id', $chosenSubjectNotes)->orderBy('created_at', 'desc')->get();
            }
            
           return response()->json(['success' => true, 'data' => ['recent' => $recent, 'recommended' => $recommended, 'browse' => $browse, 'message' => 'The data was successfully retrieved.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <deleteGroup>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <24-08-2022>
     * @Last Modified By: Vrunda Parekh(Sixty13)
     * @Last Modified:   <24-08-2022>
     * @Description:     <This function works for delete group >
     * @return \Illuminate\Http\Response
     */
    public function deleteGroup($id){
        try{
            $notes_id = CreatorGroupNote::where(['group_id' => $id])->select('notes_id', 'group_id')->pluck('notes_id')->toArray();
            CreatorGroupNote::where(['group_id' => $id])->delete();
            CreatorGroupChapter::where(['group_id' => $id])->delete();
            Group::where(['id' => $id])->delete();
            Note::whereIn('id', $notes_id)->delete();
            return response()->json(['success' => true, 'data' => ['message' => 'The group was successfully deleted.']], 200);
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <changeSubjectNotesStatus>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <24-08-2022>
     * @Last Modified By: Bansari Lathiya(Sixty13)
     * @Last Modified:   <24-08-2022>
     * @Description:     <This function works for change subject notes status (private or public) >
     * @return \Illuminate\Http\Response
     */
    public function changeSubjectNotesStatus(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'subject_id' => ['required'],
                'is_public' => ['required']
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            $data = request(['subject_id']);
            $data = request(['is_public']);
            $data['creator_id'] = auth()->guard('customer-api')->user()->id;
            startQueryLog();
            $changeStatus = CreatorSubjectNote::where(['subject_id' => $request->subject_id, 'creator_id' =>auth()->guard('customer-api')->user()->id])->update(['is_subject_public' => $request->is_public]);
            displayQueryResult();
            die;
            return response()->json(['success' => true, 'data' => [ 'message' => 'The subject note status updated successfully.']], 200);

        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }
    /**
     * @Function:        <getCountSubject>
     * @Author:          Vrunda Parekh( Sixty13 )
     * @Created On:      <25-08-2022>
     * @Last Modified By: Vrunda Parekh(Sixty13)
     * @Last Modified:   <25-08-2022>
     * @Description:     <This function works for change subject notes status (private or public) >
     * @return \Illuminate\Http\Response
     */
    public function getCountSubject(Request $request){
        try{
            $customer = auth()->guard('customer-api')->user()->id;
            $count = CustomerChosenSubject::where('customer_id', $customer)->count();
            return response()->json(['success' => true, 'data' => [ 'count' => $count]], 200);

        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }

    /**
     * @Function:        <updateProfile>
     * @Author:          Mohit Mangukia( Sixty13 )
     * @Created On:      <08-09-2022>
     * @Last Modified By: Mohit Mangukia(Sixty13)
     * @Last Modified:   <08-09-2022>
     * @Description:     <This function works for updating users profile. >
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'old_password' => ['required'],
                'new_password' => ['required', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }
    /**
     * @Function:        <profile>
     * @Author:          Bansari Lathiya( Sixty13 )
     * @Created On:      <09-09-2022>
     * @Last Modified By:Bansari Lathiya
     * @Last Modified:   <09-09-2022>
     * @Description:     <This function works for getting profile and updating profile >
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        try {
            $customer = auth()->guard('customer-api')->user();
            if($request->isMethod('post')){
                $validator = Validator::make($request->all(),[
                    'name' => ['required', 'max:30'],
                    'username' => ['required', 'regex:/^(?!.*\.\.)(?!.*\.$)[^\W][\w.]{5,29}$/', 'min:5', ValidationRule::unique('users')->ignore($customer->id), 'max:30'],
                    'email' => ['required', 'email', ValidationRule::unique('users')->ignore($customer->id), 'max:30'],
                ]);
    
                if($validator->fails()){
                    return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
                }
                $data = request(['name', 'username', 'email']);
                if($request->username == null)
                    $data['username'] = $customer->username;
                if($request->name == null)
                    $data['name'] = $customer->name;
                
                
                if($request->hasFile('image')) {
                    // Get filename with the extension
                    $filenameWithExt = $request->file('image')->getClientOriginalName();
                    // Get just filename
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    // Get just ext
                    $extension = $request->file('image')->getClientOriginalExtension();
                    // Filename to store
                    $fileNameToStore = $filename.'_'.time().'.'.$extension;
                    // Upload Image
                    $path = $request->file('image')->storeAs('public/customer_profile_images', $fileNameToStore);
                    $data['image'] = $fileNameToStore;
                }
                $customer_update = User::where('id', $customer->id)->update($data);
                $customer = User::where('id', $customer->id)->first();
                return response()->json(['success' => true, 'data' => ['result' => $customer, 'message' => 'Your profile has been updated.']], 200);
            }
           
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }
    
    /**
     * @Function:        <changePassword>
     * @Author:          Mohit Mangukia( Sixty13 )
     * @Created On:      <08-09-2022>
     * @Last Modified By: Mohit Mangukia(Sixty13)
     * @Last Modified:   <08-09-2022>
     * @Description:     <This function works for changing the password. >
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'old_password' => ['required'],
                'new_password' => ['required', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
            ]);
            if($validator->fails()){
                return response()->json(['success' => false, "error" => $this->validationMessage($validator->errors()->toArray())], 422);
            }
            if($request->old_password == $request->new_password) {
                $obj = new \stdClass();
                $obj->name = "new_password";
                $obj->message = "You can not use your old password as a new password";
                return response()->json(['success' => false, "error" => array($obj)]);
            }
            $customer = auth()->guard('customer-api')->user()->id;
            $password = bcrypt($request->old_password);
            $customer = User::findOrFail($customer);
            if(Hash::check(request('old_password'), $customer->password)) {
                $customer->password = bcrypt($request->new_password);
                $customer->save();
            } else {
                $obj = new \stdClass();
                $obj->name = "old_password";
                $obj->message = "Incorrect old password";
                return response()->json(['success' => false, "error" => array($obj)],422);
            }

            return response()->json(['success' => true, 'data' => [ 'message' => 'password changed successfully.']], 200);

        } catch (\Exception $e){
            return response()->json(['success' => false, 'error' => ['message' => 'Something went wrong.', 'error_message' => $e]], 500);
        }
    }
}