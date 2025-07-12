<?php

namespace App\Http\Controllers;

use App\Models\Item;
use \App\Models\AutoBid;
use \App\Events\ItemEvent;
use Illuminate\Http\Request;
use \App\Events\AutoBidEvent;
use \App\Events\ItemWithBidsEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Item::get();
        return $items;
    }

    public function updateLelang(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $item->name = $request->input('name');
        $item->details = $request->input('details');
        $item->min_bid = $request->input('min_bid');
        $item->available_untill = $request->input('available_untill');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }

            $path = $request->file('image')->store('images', 'public');
            $item->image = $path;
        }

        $item->save();

        return response()->json(['message' => 'Item updated successfully', 'item' => $item]);
    }

    public function indexByUserId()
    {
        $userId = auth()->id();

        $lelangs = Item::where('create_by_user_id', $userId)->get();

        return response()->json($lelangs);
    }

    // Tambah lelang baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'details' => 'required|string',
            'min_bid' => 'required|integer',
            'available_untill' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            // Simpan file ke storage/app/public/images
            $imagePath = $request->file('image')->store('images', 'public');
            // yang disimpan ke DB cuma path-nya: images/nama_file.jpg
        }

        $lelang = Item::create([
            'name' => $request->name,
            'details' => $request->details,
            'min_bid' => $request->min_bid,
            'available_untill' => $request->available_untill,
            'image' => $imagePath,
            'create_by_user_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Lelang berhasil dibuat', 'data' => $lelang], 201);
    }

    public function destroy($id)
    {
        $lelang = Item::where('id', $id)
            ->where('create_by_user_id', auth()->id())
            ->first();

        if (!$lelang) {
            return response()->json(['message' => 'Lelang tidak ditemukan atau bukan milik Anda'], 404);
        }

        $lelang->delete();

        return response()->json(['message' => 'Lelang berhasil dihapus']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {

        return $item;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        // return;

        $request->validate(
            [
                'max_bid' => 'required|integer|min:' . (int) ($request->comeFromAutoBid ? $item->max_bid : $item->max_bid + 1) . '|max:999999999',
            ]
        );

        if ($item->available_untill && now() < $item->available_untill) {

            if ($item->user_id !== Auth::id()) {
                // put auto biiding
                $itemWithBids = Item::with(['autoBids' => function ($query) use ($item) {
                    $query->where('item_id', $item->id)->orderBy('max_auto_bid');
                }])->find($item->id);

                $autoBids =  $itemWithBids->autoBids;
                $length = $autoBids->count();

                if ($this->checkCount($autoBids) && $length === 1) {
                    $firstBid = $autoBids[0];

                    if ($request->max_bid < $firstBid->max_auto_bid) {
                        $item->max_bid = $request->max_bid + 1;
                        $item->user_id = $firstBid->user_id;

                        if ($item->max_bid === $firstBid->max_auto_bid) {
                            $this->cancelAutoBid($firstBid);
                        } else {
                            $this->alertAutoBid($firstBid, $item);
                        }
                    } else {
                        $item->max_bid = $request->max_bid;
                        $item->user_id = Auth::id();

                        $this->cancelAutoBid($firstBid);
                    }
                } else if ($this->checkCount($autoBids) && $length > 1 && $request->max_bid < $autoBids[$length - 1]->max_auto_bid) {
                    $autoBidBrforeLast = $autoBids[$length - 2]->max_auto_bid;

                    if ($request->max_bid < $autoBidBrforeLast && $autoBidBrforeLast + 1 !== $autoBids[$length - 1]->max_auto_bid) {

                        $this->handle($item, $autoBidBrforeLast + 1, $autoBids[$length - 1]->user_id, $length - 1, $autoBids);

                        $this->alertAutoBid($autoBids[$length - 1], $item);
                    }
                } else {
                    $this->handle($item, $request->max_bid, Auth::id(), $length, $autoBids);
                }

                DB::table('user_item')->insertOrIgnore([
                    'user_id' => Auth::id(),
                    'item_id' => $item->id,
                ]);


                $item->save();

                $this->emitItemEvents($item);
                return response()->json([
                    'message' => 'Penawaran berhasil diajukan',
                    'item' => $item
                ], 200);
            } else {
                if ($request->comeFromAutoBid) {
                    $this->emitItemEvents($item);
                    return;
                }
                return response()->json(['message' => "You have the maximum bid"], 422);
            }
        } else {
            AutoBid::where('item_id', $item->id)->delete();
            return response()->json(['message' => "This bidding is closed"], 422);
        }
    }

    public function autoBid(Request $request, Item $item)
    {
        $request->validate(
            [
                'max_auto_bid' => 'required|integer|min:' . (int) ($item->max_bid + 1) . '|max:999999999',
                'alert_when' => 'integer|min:0|max:100',
            ]
        );
        $autoBid = new AutoBid();
        if (!$autoBid->where('user_id', Auth::id())->where('item_id', $item->id)->first()) {
            $autoBidWithDiffPrice = $autoBid->where('max_auto_bid', $request->max_auto_bid)->get();

            if (!$this->checkCount($autoBidWithDiffPrice)) {
                $autoBid->max_auto_bid = $request->max_auto_bid;
                $autoBid->alert_when = $request->alert_when;
                $autoBid->item_id = $item->id;
                $autoBid->user_id = Auth::id();
                $autoBid->save();

                $request->merge(['max_bid' => $item->max_bid, 'comeFromAutoBid' => true]);
                return $this->update($request, $item);
            } else {
                return response()->json(['message' => "You Can't make auto bid with this price"], 422);
            }
        } else {
            return response()->json(['message' => "You Can't make auto bid on this item"], 422);
        }
    }

    public function checkCount($items)
    {
        return $items->count() > 0;
    }

    public function cancelAutoBid($autoBid)
    {
        event(new AutoBidEvent($autoBid, 'canceled'));
    }

    public function handle($item, $max_bid, $user_id, $length, $autoBids)
    {
        $item->max_bid = $max_bid;
        $item->user_id = $user_id;

        for ($i = 0; $i < $length; $i++) {
            $this->cancelAutoBid($autoBids[$i]);
        }
    }

    public function alertAutoBid($autoBid, $item)
    {
        if ($autoBid->alert_when && $item->max_bid > ($autoBid->alert_when / 100) * $autoBid->max_auto_bid) {
            event(new AutoBidEvent($autoBid, 'warning'));
        }
    }

    public function emitItemEvents($item)
    {
        event(new ItemWithBidsEvent($item));
        event(new ItemEvent($item));
    }
}
