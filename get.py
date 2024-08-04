import os

def list_files(directory, output_file):
    # Ouvre le fichier de sortie en mode écriture
    with open(output_file, 'w') as f:
        # Parcours le répertoire
        for root, dirs, files in os.walk(directory):
            for file in files:
                # Écrit chaque nom de fichier dans le fichier de sortie
                f.write(os.path.join(root, file) + '\n')

if __name__ == "__main__":
    # Définir le répertoire à explorer et le fichier de sortie
    directory_to_scan = '.'  # Répertoire courant, change le selon tes besoins
    output_file_name = 'files_list.txt'

    list_files(directory_to_scan, output_file_name)
    print(f"La liste des fichiers a été écrite dans {output_file_name}")
