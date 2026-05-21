/**
 * 密码强度检测脚本
 * author: guojin
 */

function checkPasswordStrength(password) {
    let strength = 0;
    let tips = [];
    
    // 长度检查
    if (password.length >= 6) {
        strength += 1;
    } else {
        tips.push('密码至少需要6个字符');
    }
    
    // 包含数字
    if (/[0-9]/.test(password)) {
        strength += 1;
    } else {
        tips.push('建议包含数字');
    }
    
    // 包含小写字母
    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        tips.push('建议包含小写字母');
    }
    
    // 包含大写字母
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        tips.push('建议包含大写字母');
    }
    
    // 包含特殊字符
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        strength += 1;
    } else {
        tips.push('建议包含特殊字符');
    }
    
    return {
        score: strength,
        tips: tips
    };
}

function updatePasswordStrength(passwordFieldId, strengthBarId, strengthTextId) {
    const password = document.getElementById(passwordFieldId).value;
    const strengthBar = document.getElementById(strengthBarId);
    const strengthText = document.getElementById(strengthTextId);
    
    if (!strengthBar || !strengthText) return;
    
    const result = checkPasswordStrength(password);
    const percentage = (result.score / 5) * 100;
    
    // 更新进度条
    const strengthFill = strengthBar.querySelector('.strength-fill');
    if (strengthFill) {
        strengthFill.style.width = percentage + '%';
        
        // 根据强度设置颜色
        if (result.score <= 2) {
            strengthFill.style.backgroundColor = '#e74c3c';
            strengthText.textContent = '弱';
            strengthText.style.color = '#e74c3c';
        } else if (result.score <= 3) {
            strengthFill.style.backgroundColor = '#f39c12';
            strengthText.textContent = '中等';
            strengthText.style.color = '#f39c12';
        } else if (result.score <= 4) {
            strengthFill.style.backgroundColor = '#3498db';
            strengthText.textContent = '强';
            strengthText.style.color = '#3498db';
        } else {
            strengthFill.style.backgroundColor = '#27ae60';
            strengthText.textContent = '非常强';
            strengthText.style.color = '#27ae60';
        }
    }
    
    // 如果密码为空
    if (password.length === 0) {
        if (strengthFill) {
            strengthFill.style.width = '0%';
        }
        strengthText.textContent = '';
    }
}

// 确认密码验证
function validatePasswordMatch(passwordId, confirmId, messageId) {
    const password = document.getElementById(passwordId).value;
    const confirmPassword = document.getElementById(confirmId).value;
    const messageElement = document.getElementById(messageId);
    
    if (!messageElement) return true;
    
    if (password !== confirmPassword) {
        messageElement.textContent = '两次输入的密码不一致';
        messageElement.style.color = '#e74c3c';
        return false;
    } else {
        messageElement.textContent = '密码匹配';
        messageElement.style.color = '#27ae60';
        return true;
    }
}
