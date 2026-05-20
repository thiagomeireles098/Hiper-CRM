import { IsEmail, IsNotEmpty, MinLength } from "class-validator";

export class RegisterDto {
  @IsNotEmpty() workspaceName!: string;
  @IsNotEmpty() name!: string;

  @IsEmail() email!: string;
  @MinLength(6) password!: string;

  // extras (confirmado por você)
  @IsNotEmpty() cnpj!: string;
  @IsNotEmpty() phone!: string;
  @IsNotEmpty() address!: string; // MVP: string única; depois pode quebrar em campos
}

export class LoginDto {
  @IsEmail() email!: string;
  @IsNotEmpty() password!: string;
}