 <tr class="${tr_class}">
    <td style="color: ${valid}">${used_code}</td>
    <td><span id="email_${i}">${email}</span>&nbsp;<button class="btn btn-link copy_email sai_padding_off" i="${i}"><i class="fa fa-clipboard"></i></button></td>
    <td><i class="fa fa-${device}"></i></td>
    <td>${generated}</td>
    <td><i class="fa fa-${redeemed}"></i>&nbsp;${redeemed_time}</td>
    <td><i class="fa fa-${stored}"></i>&nbsp;${stored_time}</td>
    <td>${comment}</td>
    <td>
        <input type="checkbox" class="ios-check" email="${email}"/>
    </td>
</tr>