<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{$title}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row">
                <div class="col-md-12">
                    @if($groupByCards)
                        <label> Please select Card</label>
                        <select class="form-control" name="give_card">
                            @foreach($groupByCards as $groupLabel => $cardGroup)
                                <optgroup label="{{$groupLabel}}">
                                    @foreach($cardGroup as $card)
                                        <?php 
                                            $isAssign = in_array($card->id,$assignCard);
                                        ?>
                                        <option {{$isAssign ? 'disabled' : ''}} value="{{$card->id}}"> {{$card->card_name}}  {{$isAssign ? ' - Assigned' :''}} </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    @endif
                    <?php /* @if($cards)
                        <label> Please select Card</label>
                        <select class="form-control" name="give_card">
                            @foreach($cards as $card)
                                <?php 
                                    $isAssign = in_array($card->id,$assignCard);
                                ?>
                                <option {{$isAssign ? 'disabled' : ''}} value="{{$card->id}}"> {{$card->card_name}} - {{$card->card_range}} {{$isAssign ? ' - Assigned' :''}} </option>
                            @endforeach
                        </select>
                    @endif */ ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
           <form method="POST" action="javascript:void(0)" accept-charset="UTF-8">
                @csrf
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" user-id="{{ $id }}" id="saveDetail">Save</button>
            </form>
        </div>
    </div>
</div>
