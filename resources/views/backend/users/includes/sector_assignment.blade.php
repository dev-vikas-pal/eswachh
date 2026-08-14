{{--
    Sector assignment.

    Franchise Owner  -> many sectors, stored in the sector_user pivot.
    Cleaner          -> exactly one sector, stored on their own profile in the
                        same column a customer's sector lives in.

    Each block is shown only while its role is ticked; the server clears or
    ignores the value for any other role.
--}}
<div class="form-group row mb-3" id="sector-assignment" style="display: none;">
    {{ html()->label(__('Sectors'))->class('col-sm-2 form-control-label')->for('sectors') }}

    <div class="col-sm-10">
        <select name="sectors[]" id="sectors" class="form-control select2" multiple>
            @foreach($sectors as $sector)
            <option value="{{ $sector->id }}" @if(in_array($sector->id, old('sectors', $userSectors))) selected @endif>{{ $sector->name }}</option>
            @endforeach
        </select>
        <small class="form-text text-muted">
            @lang('A Franchise Owner can only see orders, customers and revenue from the sectors selected here. More than one may be selected.')
        </small>
        @error('sectors')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group row mb-3" id="cleaner-sector-assignment" style="display: none;">
    {{ html()->label(__('Sector'))->class('col-sm-2 form-control-label')->for('sector_id') }}

    <div class="col-sm-10">
        @if($showCleanerSector)
        <select name="sector_id" id="sector_id" class="form-control select2">
            <option value="">{{ __('-- Select an option --') }}</option>
            @foreach($sectors as $sector)
            <option value="{{ $sector->id }}" @if((string) old('sector_id', $userSectorId) === (string) $sector->id) selected @endif>{{ $sector->name }}</option>
            @endforeach
        </select>
        <small class="form-text text-muted">
            @lang('The sector this cleaner works in. A cleaner belongs to exactly one sector.')
        </small>
        @error('sector_id')
        <div class="text-danger">{{ $message }}</div>
        @enderror
        @else
        {{-- Editing an existing user: the Edit Profile screen already carries
             the State / City / Area / Sector / Society cascade. --}}
        <p class="form-control-plaintext mb-0">
            {{ $sectors->firstWhere('id', $userSectorId)->name ?? __('Not set') }}
            <a href="{{ route('backend.users.profileEdit', $$module_name_singular->id) }}" class="ms-2">
                @lang('Change on Edit Profile')
            </a>
        </p>
        <small class="form-text text-muted">
            @lang('A cleaner belongs to exactly one sector, kept with the rest of their location details.')
        </small>
        @endif
    </div>
</div>

<x-library.select2 />

@push('after-scripts')
<script type="module">
    $(document).ready(function() {
        var franchiseRole = $('input[name="roles[]"][value="{{ \App\Services\SectorService::FRANCHISE_ROLE }}"]');
        var cleanerRole = $('input[name="roles[]"][value="{{ \App\Services\SectorService::CLEANER_ROLE }}"]');
        // A Franchise Owner places everyone they add into one of their sectors,
        // so the picker stays visible whatever role is ticked.
        var alwaysShowSector = @json($sectorAlwaysRequired ?? false);

        function toggleSectorAssignment() {
            $('#sector-assignment').toggle(franchiseRole.is(':checked'));
            $('#cleaner-sector-assignment').toggle(alwaysShowSector || cleanerRole.is(':checked'));
        }

        franchiseRole.on('change', toggleSectorAssignment);
        cleanerRole.on('change', toggleSectorAssignment);
        toggleSectorAssignment();
    });
</script>
@endpush
