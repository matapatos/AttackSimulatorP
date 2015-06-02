/**
 * Created by Andre on 07-05-2015.
 */
var attacks = [];

/*
    RETURNS:
        -1 Doesn't exists any attack;
        index of the attack in the array.
 */
function getAttackByID(id){
    for(var i = 0; i < attacks.length; i++){
        if(attacks[i] == id)
            return i;
    }
    return -1;
}

function addAttack(id){
    if(getAttackByID(id) == -1)
        attacks.push(checkbox.name);
}

/*
    Doesn't exists a real function to remove from an Array.
    I just change the value of the specific index, to the last one, and then I remove the last one.
 */
function removeAttack(id){
    var index = getAttackByID(id);
    if(index != -1){
        var length = attacks.length;
        if(index != length)
            attacks[index] = attacks[length];
        attacks.pop();
    }
}

function addOrRemoveAttack(checkbox){
    var id = checkbox.name;
    if(checkbox.checked)
        addAttack(id);
    else removeAttack(id);
}