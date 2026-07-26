<h2>Inspections Report</h2>

<p>User: {{ $user->name }}</p>
<p>Date: {{ now() }}</p>

<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Inspection Number</th>
            <th>Business</th>
            <th>Status</th>
            <th>Inspector</th>
            <th>Created At</th>
        </tr>
    </thead>

    <tbody>
        @foreach($inspections as $i)
            <tr>
                <td>{{ $i->inspection_number }}</td>
                <td>{{ $i->business->name ?? '' }}</td>
                <td>{{ $i->status }}</td>
                <td>{{ $i->inspector->name ?? '' }}</td>
                <td>{{ $i->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>