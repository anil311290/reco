class RegisterRequestModel {
  RegisterRequestModel({
    required this.name,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    required this.companyName,
    this.phone,
    this.planSlug,
  });

  final String name;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String companyName;
  final String? phone;
  final String? planSlug;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'company_name': companyName,
      'phone': phone,
      'plan_slug': planSlug,
    };
  }
}
